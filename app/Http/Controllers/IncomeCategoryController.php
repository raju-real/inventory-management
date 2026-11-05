<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncomeCategoryRequest;
use App\Models\IncomeCategory;
use App\Services\IncomeCategoryService;
use Illuminate\Http\Request;

class IncomeCategoryController extends Controller
{
    private const PAGE = ['title' => 'Income Categories', 'word' => 'Income Category', 'prefix' => 'income-categories', 'menuKey' => 'income-categories', 'example' => 'e.g. Service Charge'];

    public function __construct(private IncomeCategoryService $service)
    {
        $this->accessResource('income-categories');
    }

    public function index(Request $request)
    {
        return view('accounts.categories.index', self::PAGE + ['categories' => $this->service->paginate($request->only('search', 'status'))]);
    }

    public function create()
    {
        return view('accounts.categories.form', self::PAGE + ['category' => new IncomeCategory(['status' => true])]);
    }

    public function store(IncomeCategoryRequest $request)
    {
        $category = $this->service->create($request->validated());

        // The + button on the income form adds a category without leaving the page.
        if ($request->expectsJson()) {
            return response()->json(['id' => $category->unique_id, 'name' => $category->name, 'label' => $category->name], 201);
        }

        return redirect()->route('income-categories.index')->with('success', 'Income category created.');
    }

    public function edit(IncomeCategory $incomeCategory)
    {
        return view('accounts.categories.form', self::PAGE + ['category' => $incomeCategory]);
    }

    public function update(IncomeCategoryRequest $request, IncomeCategory $incomeCategory)
    {
        $this->service->update($incomeCategory, $request->validated());

        return redirect()->route('income-categories.index')->with('success', 'Income category updated.');
    }

    public function destroy(IncomeCategory $incomeCategory)
    {
        $this->service->delete($incomeCategory);

        return back()->with('success', 'Income category deleted.');
    }
}
