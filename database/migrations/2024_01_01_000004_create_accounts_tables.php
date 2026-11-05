<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->char('unique_id', 10)->unique();
            $table->string('bank_name');
            $table->string('branch')->nullable();
            $table->string('account_number', 60)->unique();
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('current_balance', 14, 2)->default(0);
            $table->boolean('status')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        foreach (['expense', 'income'] as $type) {
            Schema::create("{$type}_categories", function (Blueprint $table) {
                $table->id();
                $table->char('unique_id', 10)->unique();
                $table->string('name')->unique();
                $table->string('description')->nullable();
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }

        foreach (['expenses' => 'expense_categories', 'incomes' => 'income_categories'] as $name => $categories) {
            Schema::create($name, function (Blueprint $table) use ($categories) {
                $table->id();
                $table->char('unique_id', 10)->unique();
                $table->date('entry_date')->index();
                $table->foreignId('category_id')->constrained($categories)->restrictOnDelete();
                $table->foreignId('bank_account_id')->constrained()->restrictOnDelete();
                $table->decimal('amount', 14, 2);
                $table->string('reference', 100)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        foreach (['incomes', 'expenses', 'income_categories', 'expense_categories', 'bank_accounts'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
