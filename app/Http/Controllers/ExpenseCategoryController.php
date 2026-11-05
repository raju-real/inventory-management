<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use App\Services\ExpenseCategoryService;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    private const PAGE = ['title' => 'Expense Categories', 'word' => 'Expense Category', 'prefix' => 'expense-categories', 'menuKey' => 'expense-categories', 'example' => 'e.g. Office Rent'];

    public function __construct(private ExpenseCategoryService $service)
    {
        $this->accessResource('expense-categories');
    }

    public function index(Request $request)
    {
        return view('accounts.categories.index', self::PAGE + ['categories' => $this->service->paginate($request->only('search', 'status'))]);
    }

    public function create()
    {
        return view('accounts.categories.form', self::PAGE + ['category' => new ExpenseCategory(['status' => true])]);
    }

    public function store(ExpenseCategoryRequest $request)
    {
        $category = $this->service->create($request->validated());

        // The + button on the expense form adds a category without leaving the page.
        if ($request->expectsJson()) {
            return response()->json(['id' => $category->unique_id, 'name' => $category->name, 'label' => $category->name], 201);
        }

        return redirect()->route('expense-categories.index')->with('success', 'Expense category created.');
    }

    public function edit(ExpenseCategory $expenseCategory)
    {
        return view('accounts.categories.form', self::PAGE + ['category' => $expenseCategory]);
    }

    public function update(ExpenseCategoryRequest $request, ExpenseCategory $expenseCategory)
    {
        $this->service->update($expenseCategory, $request->validated());

        return redirect()->route('expense-categories.index')->with('success', 'Expense category updated.');
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        $this->service->delete($expenseCategory);

        return back()->with('success', 'Expense category deleted.');
    }
}
