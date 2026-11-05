<?php

namespace App\Services;

use App\Models\IncomeCategory;

class IncomeCategoryService extends AccountCategoryService
{
    protected function model(): string
    {
        return IncomeCategory::class;
    }
}
