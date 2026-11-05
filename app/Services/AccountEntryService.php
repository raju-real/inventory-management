<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Shared add, edit and delete of expenses and incomes. Every step keeps the bank balance right. */
abstract class AccountEntryService extends BaseService
{
    /** @return class-string<Model> */
    abstract protected function model(): string;

    /** +1 when an entry adds money to the bank, -1 when it takes money away. */
    abstract protected function direction(): int;

    public function __construct(private BankBalanceService $balances)
    {
    }

    public function paginate(array $filters): array
    {
        $model = $this->model();

        return $model::rememberCache($this->listKey($filters), function () use ($model, $filters) {
            $query = $model::query();

            if (! empty($filters['search'])) {
                $search = '%' . $filters['search'] . '%';
                $query->where(fn ($q) => $q->where('reference', 'like', $search)->orWhere('notes', 'like', $search));
            }
            if (! empty($filters['category_id'])) {
                $query->where('category_id', $filters['category_id']);
            }
            if (! empty($filters['bank_account_id'])) {
                $query->where('bank_account_id', $filters['bank_account_id']);
            }
            if (! empty($filters['from'])) {
                $query->whereDate('entry_date', '>=', $filters['from']);
            }
            if (! empty($filters['to'])) {
                $query->whereDate('entry_date', '<=', $filters['to']);
            }

            return [
                'total' => (float) (clone $query)->sum('amount'),
                'rows' => $query->with('category', 'bankAccount', 'creator')->latest('entry_date')->latest('id')->paginate(static::PER_PAGE)->withQueryString(),
            ];
        });
    }

    public function create(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $this->balances->apply([$data['bank_account_id'] => $this->direction() * (float) $data['amount']]);
            $model = $this->model();

            return $model::create($data + ['created_by' => auth()->id()]);
        }, 3);
    }

    public function update(Model $entry, array $data): Model
    {
        return DB::transaction(function () use ($entry, $data) {
            $entry = $this->model()::whereKey($entry->id)->lockForUpdate()->firstOrFail();

            // Net effect per account, so moving money between amounts or banks never fails halfway.
            $changes = [$entry->bank_account_id => -$this->direction() * (float) $entry->amount];
            $changes[$data['bank_account_id']] = ($changes[$data['bank_account_id']] ?? 0) + $this->direction() * (float) $data['amount'];

            $this->balances->apply($changes);
            $entry->update($data);

            return $entry;
        }, 3);
    }

    public function delete(Model $entry): void
    {
        DB::transaction(function () use ($entry) {
            $entry = $this->model()::whereKey($entry->id)->lockForUpdate()->firstOrFail();

            $this->balances->apply([$entry->bank_account_id => -$this->direction() * (float) $entry->amount], 'entry');
            $entry->delete();
        }, 3);
    }
}
