<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnitRequest;
use App\Models\Unit;
use App\Services\UnitService;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function __construct(private UnitService $service)
    {
        $this->accessResource('units');
    }

    public function index(Request $request)
    {
        return view('units.index', ['units' => $this->service->paginate($request->only('search', 'status'))]);
    }

    public function create()
    {
        return view('units.form', ['unit' => new Unit(['status' => true])]);
    }

    public function store(UnitRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()->route('units.index')->with('success', 'Unit created.');
    }

    public function edit(Unit $unit)
    {
        return view('units.form', compact('unit'));
    }

    public function update(UnitRequest $request, Unit $unit)
    {
        $this->service->update($unit, $request->validated());

        return redirect()->route('units.index')->with('success', 'Unit updated.');
    }

    public function destroy(Unit $unit)
    {
        $this->service->delete($unit);

        return back()->with('success', 'Unit deleted.');
    }
}
