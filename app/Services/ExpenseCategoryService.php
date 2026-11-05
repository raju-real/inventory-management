<?php

namespace App\Services;

use App\Models\ExpenseCategory;

class ExpenseCategoryService extends AccountCategoryService
{
    protected function model(): string
    {
        return ExpenseCategory::class;
    }
}
