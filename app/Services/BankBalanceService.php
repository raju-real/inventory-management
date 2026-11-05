<?php

namespace App\Services;

use App\Models\BankAccount;
use Illuminate\Validation\ValidationException;

/**
 * The single place where a bank balance changes. Call inside DB::transaction().
 * Accounts are locked in ID order so two entries can never deadlock on each other.
 */
class BankBalanceService
{
    /** @param array $changes [bank_account_id => amount to add (negative to take away)] */
    public function apply(array $changes, string $errorField = 'amount'): void
    {
        $banks = BankAccount::whereIn('id', array_keys($changes))->orderBy('id')->lockForUpdate()->get();

        foreach ($banks as $bank) {
            $balance = round((float) $bank->current_balance + $changes[$bank->id], 2);

            if ($balance < 0) {
                throw ValidationException::withMessages([
                    $errorField => "This would take {$bank->bank_name} below zero. Current balance: " . number_format((float) $bank->current_balance, 2) . '.',
                ]);
            }

            $bank->current_balance = $balance;
            $bank->save();
        }
    }
}
