<?php

namespace App\Http\Controllers;

use App\Http\Requests\MenuRequest;
use App\Models\Menu;
use App\Services\MenuService;

class MenuController extends Controller
{
    public function __construct(private MenuService $service)
    {
        $this->middleware('admin');
    }

    public function index()
    {
        return view('menus.index', [
            'menus' => Menu::with('parent')->orderByRaw('COALESCE(parent_id, id), parent_id IS NOT NULL, sort_order')->paginate(50),
        ]);
    }

    public function edit(Menu $menu)
    {
        return view('menus.form', [
            'menu' => $menu,
            'parents' => Menu::whereNull('route')->orderBy('sort_order')->pluck('name', 'unique_id'),
        ]);
    }

    public function update(MenuRequest $request, Menu $menu)
    {
        $this->service->update($menu, $request->validated());

        return redirect()->route('menus.index')->with('success', 'Menu updated.');
    }
}
