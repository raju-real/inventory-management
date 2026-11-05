<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Validation\ValidationException;

class CustomerService extends BaseService
{
    public function __construct(private ImageService $images)
    {
    }

    public function paginate(array $filters)
    {
        return Customer::rememberCache($this->listKey($filters), function () use ($filters) {
            $query = Customer::withBalance()->latest('id');
            $this->applyStatusFilter($query, $filters);

            if (! empty($filters['search'])) {
                $search = '%' . $filters['search'] . '%';
                $query->where(fn ($q) => $q->where('name', 'like', $search)->orWhere('mobile', 'like', $search)->orWhere('email', 'like', $search));
            }

            return $query->paginate(static::PER_PAGE)->withQueryString();
        });
    }

    public function create(array $data): Customer
    {
        $data['opening_balance'] = $data['opening_balance'] ?? 0;

        return Customer::create($this->images->resolve($data, null, 'customers'));
    }

    public function update(Customer $customer, array $data): Customer
    {
        $data['opening_balance'] = $data['opening_balance'] ?? 0;
        $customer->update($this->images->resolve($data, $customer, 'customers'));

        return $customer;
    }

    public function delete(Customer $customer): void
    {
        if ($customer->sales()->exists() || $customer->payments()->exists()) {
            throw ValidationException::withMessages(['customer' => 'This customer has transactions and cannot be deleted. Deactivate them instead.']);
        }

        $customer->delete();
        $this->images->delete($customer->image);
    }
}
