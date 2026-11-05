<?php

namespace App\Http\Controllers;

use App\Http\Requests\BarcodeSettingRequest;
use App\Services\SettingService;

class BarcodeController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function settings()
    {
        return view('settings.barcode');
    }

    public function update(BarcodeSettingRequest $request, SettingService $settings)
    {
        $settings->save($request->validated());

        return redirect()->route('barcode.settings')->with('success', 'Barcode settings saved.');
    }
}
