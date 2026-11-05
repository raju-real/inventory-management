<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Validation\ValidationException;

class SupplierService extends BaseService
{
    public function __construct(private ImageService $images)
    {
    }

    public function paginate(array $filters)
    {
        return Supplier::rememberCache($this->listKey($filters), function () use ($filters) {
            $query = Supplier::withBalance()->latest('id');
            $this->applyStatusFilter($query, $filters);

            if (! empty($filters['search'])) {
                $search = '%' . $filters['search'] . '%';
                $query->where(fn ($q) => $q->where('name', 'like', $search)
                    ->orWhere('contact_person', 'like', $search)
                    ->orWhere('mobile', 'like', $search)
                    ->orWhere('email', 'like', $search));
            }

            return $query->paginate(static::PER_PAGE)->withQueryString();
        });
    }

    public function create(array $data): Supplier
    {
        $data['opening_balance'] = $data['opening_balance'] ?? 0;

        return Supplier::create($this->images->resolve($data, null, 'suppliers'));
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $data['opening_balance'] = $data['opening_balance'] ?? 0;
        $supplier->update($this->images->resolve($data, $supplier, 'suppliers'));

        return $supplier;
    }

    public function delete(Supplier $supplier): void
    {
        if ($supplier->purchases()->exists() || $supplier->payments()->exists()) {
            throw ValidationException::withMessages(['supplier' => 'This supplier has transactions and cannot be deleted. Deactivate them instead.']);
        }

        $supplier->delete();
        $this->images->delete($supplier->image);
    }
}
