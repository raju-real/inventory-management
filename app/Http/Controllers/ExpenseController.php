<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\ExpenseService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    private const PAGE = ['title' => 'Expenses', 'word' => 'Expense', 'prefix' => 'expenses', 'menuKey' => 'expenses', 'tone' => 'out', 'categoryMenu' => 'expense-categories', 'categoryWord' => 'Expense Category', 'categoryExample' => 'e.g. Office Rent', 'effect' => 'The bank balance goes down by this amount.'];

    public function __construct(private ExpenseService $service)
    {
        $this->accessResource('expenses');
    }

    public function index(Request $request)
    {
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $filters = $this->resolveIds($request->only('search', 'category_id', 'bank_account_id', 'from', 'to'), ['category_id' => ExpenseCategory::class, 'bank_account_id' => BankAccount::class]);

        return view('accounts.entries.index', self::PAGE + $this->service->paginate($filters) + [
            'categories' => ExpenseCategory::orderBy('name')->get(['id', 'unique_id', 'name']),
            'banks' => BankAccount::orderBy('bank_name')->get(),
        ]);
    }

    public function create()
    {
        return view('accounts.entries.form', self::PAGE + $this->options(new Expense()) + ['entry' => new Expense()]);
    }

    public function store(ExpenseRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()->route('expenses.index')->with('success', 'Expense saved and bank balance updated.');
    }

    public function edit(Expense $expense)
    {
        return view('accounts.entries.form', self::PAGE + $this->options($expense) + ['entry' => $expense]);
    }

    public function update(ExpenseRequest $request, Expense $expense)
    {
        $this->service->update($expense, $request->validated());

        return redirect()->route('expenses.index')->with('success', 'Expense updated and bank balance adjusted.');
    }

    public function destroy(Expense $expense)
    {
        $this->service->delete($expense);

        return back()->with('success', 'Expense deleted and bank balance restored.');
    }

    /** Active choices, plus whatever this entry already uses even if it was deactivated later. */
    private function options(Expense $entry): array
    {
        return [
            'categories' => ExpenseCategory::where('status', true)->orWhere('id', $entry->category_id)->orderBy('name')->get(),
            'banks' => BankAccount::where('status', true)->orWhere('id', $entry->bank_account_id)->orderBy('bank_name')->get(),
        ];
    }
}
