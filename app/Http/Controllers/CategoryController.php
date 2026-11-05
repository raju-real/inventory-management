<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(private CategoryService $service)
    {
        $this->accessResource('categories');
    }

    public function index(Request $request)
    {
        return view('categories.index', ['categories' => $this->service->paginate($request->only('search', 'status'))]);
    }

    public function create()
    {
        return view('categories.form', ['category' => new Category(['status' => true])]);
    }

    public function store(CategoryRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()->route('categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category)
    {
        return view('categories.form', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $this->service->update($category, $request->validated());

        return redirect()->route('categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $this->service->delete($category);

        return back()->with('success', 'Category deleted.');
    }
}
