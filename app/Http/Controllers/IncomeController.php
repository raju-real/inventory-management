<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncomeRequest;
use App\Models\BankAccount;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Services\IncomeService;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    private const PAGE = ['title' => 'Incomes', 'word' => 'Income', 'prefix' => 'incomes', 'menuKey' => 'incomes', 'tone' => 'in', 'categoryMenu' => 'income-categories', 'categoryWord' => 'Income Category', 'categoryExample' => 'e.g. Service Charge', 'effect' => 'The bank balance goes up by this amount.'];

    public function __construct(private IncomeService $service)
    {
        $this->accessResource('incomes');
    }

    public function index(Request $request)
    {
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $filters = $this->resolveIds($request->only('search', 'category_id', 'bank_account_id', 'from', 'to'), ['category_id' => IncomeCategory::class, 'bank_account_id' => BankAccount::class]);

        return view('accounts.entries.index', self::PAGE + $this->service->paginate($filters) + [
            'categories' => IncomeCategory::orderBy('name')->get(['id', 'unique_id', 'name']),
            'banks' => BankAccount::orderBy('bank_name')->get(),
        ]);
    }

    public function create()
    {
        return view('accounts.entries.form', self::PAGE + $this->options(new Income()) + ['entry' => new Income()]);
    }

    public function store(IncomeRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()->route('incomes.index')->with('success', 'Income saved and bank balance updated.');
    }

    public function edit(Income $income)
    {
        return view('accounts.entries.form', self::PAGE + $this->options($income) + ['entry' => $income]);
    }

    public function update(IncomeRequest $request, Income $income)
    {
        $this->service->update($income, $request->validated());

        return redirect()->route('incomes.index')->with('success', 'Income updated and bank balance adjusted.');
    }

    public function destroy(Income $income)
    {
        $this->service->delete($income);

        return back()->with('success', 'Income deleted and bank balance adjusted.');
    }

    /** Active choices, plus whatever this entry already uses even if it was deactivated later. */
    private function options(Income $entry): array
    {
        return [
            'categories' => IncomeCategory::where('status', true)->orWhere('id', $entry->category_id)->orderBy('name')->get(),
            'banks' => BankAccount::where('status', true)->orWhere('id', $entry->bank_account_id)->orderBy('bank_name')->get(),
        ];
    }
}
