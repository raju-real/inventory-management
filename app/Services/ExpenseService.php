<?php

namespace App\Services;

use App\Models\Expense;

class ExpenseService extends AccountEntryService
{
    protected function model(): string
    {
        return Expense::class;
    }

    protected function direction(): int
    {
        return -1;
    }
}
