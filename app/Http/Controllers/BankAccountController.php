<?php

namespace App\Http\Controllers;

use App\Http\Requests\BankAccountRequest;
use App\Models\BankAccount;
use App\Services\BankAccountService;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function __construct(private BankAccountService $service)
    {
        $this->accessResource('bank-accounts');
    }

    public function index(Request $request)
    {
        return view('accounts.banks.index', [
            'banks' => $this->service->paginate($request->only('search', 'status')),
            'summary' => $this->service->summary(),
        ]);
    }

    public function create()
    {
        return view('accounts.banks.form', ['bank' => new BankAccount(['status' => true])]);
    }

    public function store(BankAccountRequest $request)
    {
        $bank = $this->service->create($request->validated());

        return redirect()->route('bank-accounts.show', $bank)->with('success', 'Bank account added.');
    }

    public function show(Request $request, BankAccount $bankAccount)
    {
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);

        return view('accounts.banks.show', [
            'bank' => $bankAccount,
            'statement' => $this->service->statement($bankAccount, $request->only('from', 'to')),
        ]);
    }

    public function edit(BankAccount $bankAccount)
    {
        return view('accounts.banks.form', ['bank' => $bankAccount]);
    }

    public function update(BankAccountRequest $request, BankAccount $bankAccount)
    {
        $this->service->update($bankAccount, $request->validated());

        return redirect()->route('bank-accounts.show', $bankAccount)->with('success', 'Bank account updated.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        $this->service->delete($bankAccount);

        return redirect()->route('bank-accounts.index')->with('success', 'Bank account deleted.');
    }
}
