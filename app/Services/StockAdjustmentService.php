<?php

namespace App\Services;

use App\Models\StockAdjustment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockAdjustmentService extends BaseService
{
    public function __construct(private StockService $stock)
    {
    }

    public function paginate(array $filters)
    {
        return StockAdjustment::rememberCache($this->listKey($filters), function () use ($filters) {
            $query = StockAdjustment::with('product.unit', 'creator')->latest('id');

            if (! empty($filters['search'])) {
                $search = '%' . $filters['search'] . '%';
                $query->where(fn ($q) => $q->where('reference_no', 'like', $search)
                    ->orWhereHas('product', fn ($p) => $p->where('name', 'like', $search)->orWhere('sku', 'like', $search)));
            }
            if (! empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            return $query->paginate(static::PER_PAGE)->withQueryString();
        });
    }

    public function create(array $data): StockAdjustment
    {
        return DB::transaction(function () use ($data) {
            $adjustment = StockAdjustment::create($data);

            if ($adjustment->type === 'increase') {
                $this->stock->in($adjustment->product_id, (float) $adjustment->quantity, 'adjustment', $adjustment, $data['adjustment_date'], null, $data['reason']);
            } else {
                $this->stock->out($adjustment->product_id, (float) $adjustment->quantity, 'adjustment', $adjustment, $data['adjustment_date'], null, $data['reason']);
            }

            return $adjustment;
        }, 3);
    }

    public function cancel(StockAdjustment $adjustment, string $reason): void
    {
        DB::transaction(function () use ($adjustment, $reason) {
            $adjustment = StockAdjustment::whereKey($adjustment->id)->lockForUpdate()->firstOrFail();

            if ($adjustment->isCancelled()) {
                throw ValidationException::withMessages(['adjustment' => 'This adjustment is already cancelled.']);
            }

            $date = now()->toDateString();
            if ($adjustment->type === 'increase') {
                $this->stock->out($adjustment->product_id, (float) $adjustment->quantity, 'adjustment_cancel', $adjustment, $date, null, 'Cancelled: ' . $reason);
            } else {
                $this->stock->in($adjustment->product_id, (float) $adjustment->quantity, 'adjustment_cancel', $adjustment, $date, null, 'Cancelled: ' . $reason);
            }

            $adjustment->markCancelled($reason);
        }, 3);
    }
}
