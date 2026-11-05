<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $service)
    {
        $this->middleware('access:dashboard,view');
    }

    public function index()
    {
        return view('dashboard', ['stats' => $this->service->stats(), 'accounts' => $this->service->accounts()]);
    }
}
