<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelRequest;
use App\Http\Requests\DamageRequest;
use App\Models\Damage;
use App\Services\DamageService;
use App\Services\ProductService;
use Illuminate\Http\Request;

class DamageController extends Controller
{
    public function __construct(private DamageService $service)
    {
        $this->accessResource('damages');
    }

    public function index(Request $request)
    {
        return view('damages.index', ['damages' => $this->service->paginate($request->only('search', 'status', 'from', 'to'))]);
    }

    public function create(ProductService $products)
    {
        return view('damages.create', ['oldItems' => $products->hydrateOldItems(old('items', []), 'damage')]);
    }

    public function store(DamageRequest $request)
    {
        $damage = $this->service->create($request->validated());

        return redirect()->route('damages.show', $damage)->with('success', "Damage {$damage->reference_no} recorded and stock updated.");
    }

    public function show(Damage $damage)
    {
        return view('damages.show', ['damage' => $damage->load('details.product.unit', 'creator', 'canceller')]);
    }

    public function cancel(CancelRequest $request, Damage $damage)
    {
        $this->service->cancel($damage, $request->validated()['cancel_reason']);

        return back()->with('success', 'Damage entry cancelled and stock restored.');
    }
}
