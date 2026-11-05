<?php

namespace App\Services;

use App\Models\BankAccount;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class BankAccountService extends BaseService
{
    public function paginate(array $filters)
    {
        return BankAccount::rememberCache($this->listKey($filters), function () use ($filters) {
            $query = BankAccount::latest('id');
            $this->applyStatusFilter($query, $filters);

            if (! empty($filters['search'])) {
                $search = '%' . $filters['search'] . '%';
                $query->where(fn ($q) => $q->where('bank_name', 'like', $search)
                    ->orWhere('branch', 'like', $search)
                    ->orWhere('account_number', 'like', $search));
            }

            return $query->paginate(9)->withQueryString();
        });
    }

    public function summary(): array
    {
        return BankAccount::rememberCache('summary', fn () => [
            'count' => BankAccount::where('status', true)->count(),
            'balance' => (float) BankAccount::where('status', true)->sum('current_balance'),
        ]);
    }

    public function create(array $data): BankAccount
    {
        $opening = (float) ($data['opening_balance'] ?? 0);

        return BankAccount::create(array_merge($data, [
            'opening_balance' => $opening,
            'current_balance' => $opening,
            'created_by' => auth()->id(),
        ]));
    }

    public function update(BankAccount $bank, array $data): BankAccount
    {
        // The balance only ever changes through incomes and expenses.
        unset($data['opening_balance'], $data['current_balance']);
        $bank->update($data);

        return $bank;
    }

    public function delete(BankAccount $bank): void
    {
        if ($bank->expenses()->exists() || $bank->incomes()->exists()) {
            throw ValidationException::withMessages(['bank_account' => 'This account has transactions and cannot be deleted. Deactivate it instead.']);
        }

        $bank->delete();
    }

    /** Every income and expense of an account in date order, with a running balance. */
    public function statement(BankAccount $bank, array $filters): array
    {
        $row = fn ($entry, string $type, float $in, float $out) => [
            'date' => $entry->entry_date->toDateString(),
            'order' => $entry->created_at?->timestamp . '-' . $entry->id,
            'type' => $type,
            'category' => $entry->category?->name,
            'reference' => $entry->reference,
            'notes' => $entry->notes,
            'in' => $in,
            'out' => $out,
        ];

        $rows = collect();
        foreach ($bank->incomes()->with('category')->get() as $income) {
            $rows->push($row($income, 'Income', (float) $income->amount, 0));
        }
        foreach ($bank->expenses()->with('category')->get() as $expense) {
            $rows->push($row($expense, 'Expense', 0, (float) $expense->amount));
        }

        $rows = $rows->sortBy(fn ($item) => $item['date'] . '|' . $item['order'])->values();
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        $opening = (float) $bank->opening_balance + $rows->filter(fn ($item) => $from && $item['date'] < $from)->sum(fn ($item) => $item['in'] - $item['out']);

        $balance = $opening;
        $visible = $rows
            ->filter(fn ($item) => (! $from || $item['date'] >= $from) && (! $to || $item['date'] <= $to))
            ->map(function ($item) use (&$balance) {
                $balance += $item['in'] - $item['out'];
                $item['balance'] = round($balance, 2);

                return $item;
            })
            ->values();

        $page = max(1, (int) request('page', 1));

        return [
            'rows' => new LengthAwarePaginator($visible->forPage($page, static::PER_PAGE)->values(), $visible->count(), static::PER_PAGE, $page, ['path' => request()->url(), 'query' => request()->query()]),
            'opening' => round($opening, 2),
            'in' => round($visible->sum('in'), 2),
            'out' => round($visible->sum('out'), 2),
            'closing' => round($balance, 2),
        ];
    }
}
