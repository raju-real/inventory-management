<?php

namespace App\Services;

use App\Models\Damage;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DamageService extends BaseService
{
    public function __construct(private StockService $stock)
    {
    }

    public function paginate(array $filters)
    {
        return Damage::rememberCache($this->listKey($filters), function () use ($filters) {
            $query = Damage::with('creator')->withCount('details')->latest('id');

            if (! empty($filters['search'])) {
                $query->where('reference_no', 'like', '%' . $filters['search'] . '%');
            }
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (! empty($filters['from'])) {
                $query->whereDate('damage_date', '>=', $filters['from']);
            }
            if (! empty($filters['to'])) {
                $query->whereDate('damage_date', '<=', $filters['to']);
            }

            return $query->paginate(static::PER_PAGE)->withQueryString();
        });
    }

    public function create(array $data): Damage
    {
        return DB::transaction(function () use ($data) {
            $this->stock->lockProducts(array_column($data['items'], 'product_id'));
            $damage = Damage::create([
                'damage_date' => $data['damage_date'],
                'total_quantity' => collect($data['items'])->sum(fn ($item) => (float) $item['quantity']),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $cost = (float) Product::whereKey($item['product_id'])->value('purchase_price');

                $damage->details()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $cost,
                    'reason' => $item['reason'] ?? null,
                ]);

                $this->stock->out((int) $item['product_id'], (float) $item['quantity'], 'damage', $damage, $data['damage_date'], $cost, $item['reason'] ?? null);
            }

            return $damage;
        }, 3);
    }

    public function cancel(Damage $damage, string $reason): void
    {
        DB::transaction(function () use ($damage, $reason) {
            $damage = Damage::whereKey($damage->id)->lockForUpdate()->firstOrFail();

            if ($damage->isCancelled()) {
                throw ValidationException::withMessages(['damage' => 'This damage entry is already cancelled.']);
            }

            $this->stock->lockProducts($damage->details->pluck('product_id'));

            foreach ($damage->details as $detail) {
                $this->stock->in($detail->product_id, (float) $detail->quantity, 'damage_cancel', $damage, now()->toDateString(), (float) $detail->unit_cost, 'Cancelled: ' . $reason);
            }

            $damage->markCancelled($reason);
        }, 3);
    }
}
