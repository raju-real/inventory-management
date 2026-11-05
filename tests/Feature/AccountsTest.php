<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\IncomeCategory;
use Tests\TestCase;

class AccountsTest extends TestCase
{
    private function bank(float $balance = 1000, string $number = '111'): BankAccount
    {
        $this->actingAs($this->admin())->post(route('bank-accounts.store'), [
            'bank_name' => 'City Bank', 'branch' => 'Gulshan', 'account_number' => $number, 'opening_balance' => $balance, 'status' => 1,
        ])->assertSessionHasNoErrors();

        return BankAccount::where('account_number', $number)->first();
    }

    private function entry(string $route, BankAccount $bank, $amount, $category, array $extra = [])
    {
        return $this->post(route($route), array_merge([
            'entry_date' => now()->toDateString(), 'category_id' => $category->unique_id, 'bank_account_id' => $bank->unique_id, 'amount' => $amount,
        ], $extra));
    }

    private function assertBalance(BankAccount $bank, float $expected): void
    {
        $bank->refresh();
        $this->assertEquals($expected, (float) $bank->current_balance);

        // The stored balance must always equal the opening balance plus all its entries.
        $computed = (float) $bank->opening_balance + (float) Income::where('bank_account_id', $bank->id)->sum('amount') - (float) Expense::where('bank_account_id', $bank->id)->sum('amount');
        $this->assertEquals($expected, round($computed, 2));
    }

    private function expenseCategory(): ExpenseCategory
    {
        return ExpenseCategory::create(['name' => 'Rent ' . uniqid()]);
    }

    private function incomeCategory(): IncomeCategory
    {
        return IncomeCategory::create(['name' => 'Service ' . uniqid()]);
    }

    public function test_adding_income_and_expense_updates_the_balance()
    {
        $bank = $this->bank(1000);
        $this->assertBalance($bank, 1000);

        $this->entry('incomes.store', $bank, 500, $this->incomeCategory())->assertSessionHasNoErrors();
        $this->assertBalance($bank, 1500);

        $this->entry('expenses.store', $bank, 200.50, $this->expenseCategory())->assertSessionHasNoErrors();
        $this->assertBalance($bank, 1299.50);
    }

    public function test_editing_the_amount_adjusts_the_balance_by_the_difference()
    {
        $bank = $this->bank(1000);
        $category = $this->expenseCategory();
        $this->entry('expenses.store', $bank, 300, $category);
        $expense = Expense::first();
        $payload = ['entry_date' => now()->toDateString(), 'category_id' => $category->unique_id, 'bank_account_id' => $bank->unique_id];

        $this->put(route('expenses.update', $expense), $payload + ['amount' => 100])->assertSessionHasNoErrors();
        $this->assertBalance($bank, 900);
        $this->put(route('expenses.update', $expense), $payload + ['amount' => 900])->assertSessionHasNoErrors();
        $this->assertBalance($bank, 100);

        $income = $this->incomeCategory();
        $this->entry('incomes.store', $bank, 50, $income);
        $this->put(route('incomes.update', Income::first()), ['entry_date' => now()->toDateString(), 'category_id' => $income->unique_id, 'bank_account_id' => $bank->unique_id, 'amount' => 75])->assertSessionHasNoErrors();
        $this->assertBalance($bank, 175);
    }

    public function test_moving_an_entry_to_another_bank_moves_the_money()
    {
        $first = $this->bank(1000, '111');
        $second = $this->bank(1000, '222');
        $category = $this->expenseCategory();
        $this->entry('expenses.store', $first, 300, $category);

        $this->put(route('expenses.update', Expense::first()), [
            'entry_date' => now()->toDateString(), 'category_id' => $category->unique_id, 'bank_account_id' => $second->unique_id, 'amount' => 400,
        ])->assertSessionHasNoErrors();

        $this->assertBalance($first, 1000);
        $this->assertBalance($second, 600);
    }

    public function test_deleting_an_entry_restores_the_balance()
    {
        $bank = $this->bank(1000);
        $this->entry('expenses.store', $bank, 300, $this->expenseCategory());
        $this->entry('incomes.store', $bank, 100, $this->incomeCategory());
        $this->assertBalance($bank, 800);

        $this->delete(route('expenses.destroy', Expense::first()))->assertSessionHasNoErrors();
        $this->assertBalance($bank, 1100);
        $this->delete(route('incomes.destroy', Income::first()))->assertSessionHasNoErrors();
        $this->assertBalance($bank, 1000);
    }

    public function test_the_balance_cannot_go_below_zero_and_nothing_is_saved()
    {
        $bank = $this->bank(100);
        $category = $this->expenseCategory();

        $this->entry('expenses.store', $bank, 100.01, $category)->assertSessionHasErrors('amount');
        $this->assertSame(0, Expense::count());
        $this->assertBalance($bank, 100);

        // An income that has already been spent cannot be reduced or removed.
        $income = $this->incomeCategory();
        $this->entry('incomes.store', $bank, 500, $income);
        $this->entry('expenses.store', $bank, 550, $category)->assertSessionHasNoErrors();
        $this->assertBalance($bank, 50);

        $this->delete(route('incomes.destroy', Income::first()))->assertSessionHasErrors('entry');
        $this->put(route('incomes.update', Income::first()), ['entry_date' => now()->toDateString(), 'category_id' => $income->unique_id, 'bank_account_id' => $bank->unique_id, 'amount' => 100])
            ->assertSessionHasErrors('amount');
        $this->assertBalance($bank, 50);
    }

    public function test_editing_the_account_cannot_change_its_balance()
    {
        $bank = $this->bank(1000);

        $this->put(route('bank-accounts.update', $bank), [
            'bank_name' => 'Renamed', 'account_number' => '111', 'status' => 1, 'opening_balance' => 99999, 'current_balance' => 99999,
        ])->assertSessionHasNoErrors();

        $this->assertBalance($bank, 1000);
        $this->assertSame('Renamed', $bank->name ?? $bank->fresh()->bank_name);
    }

    public function test_accounts_and_categories_in_use_cannot_be_deleted()
    {
        $bank = $this->bank(1000);
        $category = $this->expenseCategory();
        $this->entry('expenses.store', $bank, 10, $category);

        $this->delete(route('bank-accounts.destroy', $bank))->assertSessionHasErrors('bank_account');
        $this->delete(route('expense-categories.destroy', $category))->assertSessionHasErrors('category');

        $free = $this->bank(0, '999');
        $this->delete(route('bank-accounts.destroy', $free))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('bank_accounts', ['id' => $free->id]);
    }

    public function test_statement_shows_a_running_balance_and_carries_earlier_entries_forward()
    {
        $bank = $this->bank(1000);
        $this->entry('expenses.store', $bank, 100, $this->expenseCategory(), ['entry_date' => now()->subDays(10)->toDateString()]);
        $this->entry('incomes.store', $bank, 300, $this->incomeCategory());

        $statement = app(\App\Services\BankAccountService::class)->statement($bank->fresh(), ['from' => now()->subDay()->toDateString()]);
        $this->assertEquals(900, $statement['opening']);
        $this->assertEquals(1200, $statement['closing']);
        $this->assertEquals(300, $statement['in']);

        $this->get(route('bank-accounts.show', $bank))->assertOk()->assertSee('1,200.00');
    }

    public function test_validation_and_permissions()
    {
        $bank = $this->bank(100);
        $this->post(route('expenses.store'), [])->assertSessionHasErrors(['entry_date', 'category_id', 'bank_account_id', 'amount']);
        $this->post(route('bank-accounts.store'), ['account_number' => '111'])->assertSessionHasErrors(['bank_name', 'account_number', 'status']);
        $this->entry('expenses.store', $bank, 0, $this->expenseCategory())->assertSessionHasErrors('amount');
        $this->post(route('expense-categories.store'), [])->assertSessionHasErrors(['name', 'status']);
        $this->post(route('income-categories.store'), [])->assertSessionHasErrors(['name', 'status']);

        $viewer = $this->staff(['expenses' => ['view']]);
        $this->actingAs($viewer)->get(route('expenses.index'))->assertOk()->assertDontSee('Add Expense');
        $this->get(route('expenses.create'))->assertForbidden();
        $this->get(route('bank-accounts.index'))->assertForbidden();
        $this->get(route('dashboard'))->assertForbidden();
    }

    public function test_the_dashboard_only_shows_money_to_users_with_access()
    {
        $this->bank(4321);
        $this->get(route('dashboard'))->assertOk()->assertSee('4,321.00');

        $this->actingAs($this->staff(['dashboard' => ['view']]))->get(route('dashboard'))->assertOk()->assertDontSee('4,321.00');
    }

    public function test_a_category_can_be_added_from_the_entry_form()
    {
        $this->actingAs($this->admin());

        $this->postJson(route('expense-categories.store'), ['name' => 'Fuel', 'status' => 1])
            ->assertCreated()->assertJson(['name' => 'Fuel', 'label' => 'Fuel', 'id' => ExpenseCategory::where('name', 'Fuel')->value('unique_id')]);
        $this->postJson(route('income-categories.store'), ['name' => 'Rent Received', 'status' => 1])
            ->assertCreated()->assertJson(['id' => IncomeCategory::where('name', 'Rent Received')->value('unique_id')]);

        // Errors come back as JSON for the popup to show.
        $this->postJson(route('expense-categories.store'), ['name' => 'Fuel', 'status' => 1])->assertStatus(422)->assertJsonValidationErrors('name');
        $this->postJson(route('expense-categories.store'), ['status' => 1])->assertStatus(422)->assertJsonValidationErrors('name');

        // The new category is in the dropdown straight away, and a normal form post still redirects.
        $this->get(route('expenses.create'))->assertSee('Fuel');
        $this->post(route('expense-categories.store'), ['name' => 'Tea', 'status' => 1])->assertRedirect(route('expense-categories.index'));
    }

    public function test_the_category_button_and_top_bar_links_follow_permissions()
    {
        $admin = $this->actingAs($this->admin())->get(route('expenses.create'))->assertOk();
        $admin->assertSee('id="categoryModal"', false)->assertSee('New Income')->assertSee('New Expense');
        $this->get(route('incomes.create'))->assertSee('Add Income Category')->assertSee(route('income-categories.store'));

        $clerk = $this->staff(['expenses' => ['view', 'create']]);
        $page = $this->actingAs($clerk)->get(route('expenses.create'))->assertOk();
        $page->assertDontSee('id="categoryModal"', false)->assertSee('New Expense')->assertDontSee('New Income')->assertDontSee('New Sale');
        $page->assertSee('at least one', false);
        $this->postJson(route('expense-categories.store'), ['name' => 'Sneaky', 'status' => 1])->assertForbidden();

        $viewer = $this->staff(['expenses' => ['view']]);
        $this->actingAs($viewer)->get(route('expenses.index'))->assertDontSee('New Expense')->assertDontSee('New Income');
    }

    public function test_the_accounts_menu_lists_bank_accounts_first()
    {
        $group = \App\Models\Menu::where('key', 'accounts')->first();

        $this->assertSame(['bank-accounts', 'expense-categories', 'expenses', 'income-categories', 'incomes'], $group->children()->pluck('key')->all());
    }
}
