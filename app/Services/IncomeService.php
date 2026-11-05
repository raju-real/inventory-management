<?php

namespace App\Services;

use App\Models\Income;

class IncomeService extends AccountEntryService
{
    protected function model(): string
    {
        return Income::class;
    }

    protected function direction(): int
    {
        return 1;
    }
}
