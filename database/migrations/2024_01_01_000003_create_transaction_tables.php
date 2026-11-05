<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->char('unique_id', 10)->unique();
            $table->string('reference_no', 60)->unique();
            $table->date('purchase_date')->index();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->decimal('total_quantity', 14, 3)->default(0);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('other_charges', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('returned_amount', 14, 2)->default(0);
            $table->decimal('due_amount', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $this->documentColumns($table);
        });

        Schema::create('purchase_details', function (Blueprint $table) {
            $table->id();
            $table->char('unique_id', 10)->unique();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('returned_quantity', 14, 3)->default(0);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('total', 14, 2);
            $table->timestamps();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->char('unique_id', 10)->unique();
            $table->string('reference_no', 60)->unique();
            $table->date('sale_date')->index();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->decimal('total_quantity', 14, 3)->default(0);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('returned_amount', 14, 2)->default(0);
            $table->decimal('due_amount', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $this->documentColumns($table);
        });

        Schema::create('sale_details', function (Blueprint $table) {
            $table->id();
            $table->char('unique_id', 10)->unique();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('returned_quantity', 14, 3)->default(0);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('total', 14, 2);
            $table->timestamps();
        });

        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->char('unique_id', 10)->unique();
            $table->string('reference_no', 60)->unique();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->date('return_date')->index();
            $table->decimal('total_quantity', 14, 3)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('refund_amount', 14, 2)->default(0);
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $this->documentColumns($table);
        });

        Schema::create('sales_return_details', function (Blueprint $table) {
            $table->id();
            $table->char('unique_id', 10)->unique();
            $table->foreignId('sales_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_detail_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('total', 14, 2);
            $table->string('condition', 20)->default('good');
            $table->timestamps();
        });

        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->char('unique_id', 10)->unique();
            $table->string('reference_no', 60)->unique();
            $table->foreignId('purchase_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->date('return_date')->index();
            $table->decimal('total_quantity', 14, 3)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('refund_amount', 14, 2)->default(0);
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $this->documentColumns($table);
        });

        Schema::create('purchase_return_details', function (Blueprint $table) {
            $table->id();
            $table->char('unique_id', 10)->unique();
            $table->foreignId('purchase_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_detail_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('total', 14, 2);
            $table->timestamps();
        });

        Schema::create('damages', function (Blueprint $table) {
            $table->id();
            $table->char('unique_id', 10)->unique();
            $table->string('reference_no', 60)->unique();
            $table->date('damage_date')->index();
            $table->decimal('total_quantity', 14, 3)->default(0);
            $table->text('notes')->nullable();
            $this->documentColumns($table);
        });

        Schema::create('damage_details', function (Blueprint $table) {
            $table->id();
            $table->char('unique_id', 10)->unique();
            $table->foreignId('damage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->char('unique_id', 10)->unique();
            $table->string('reference_no', 60)->unique();
            $table->date('adjustment_date')->index();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('type', 20);
            $table->decimal('quantity', 14, 3);
            $table->string('reason');
            $this->documentColumns($table);
        });

        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->char('unique_id', 10)->unique();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->date('transaction_date')->index();
            $table->string('transaction_type', 40)->index();
            $table->string('reference_type', 60)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_no', 60)->nullable();
            $table->decimal('quantity_in', 14, 3)->default(0);
            $table->decimal('quantity_out', 14, 3)->default(0);
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->string('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->char('unique_id', 10)->unique();
            $table->string('reference_no', 60)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('payment_date')->index();
            $table->decimal('amount', 14, 2);
            $table->string('method', 30)->default('cash');
            $table->string('transaction_ref')->nullable();
            $table->text('notes')->nullable();
            $this->documentColumns($table);
        });

        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->char('unique_id', 10)->unique();
            $table->string('reference_no', 60)->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('payment_date')->index();
            $table->decimal('amount', 14, 2);
            $table->string('method', 30)->default('cash');
            $table->string('transaction_ref')->nullable();
            $table->text('notes')->nullable();
            $this->documentColumns($table);
        });
    }

    public function down()
    {
        foreach (['supplier_payments', 'customer_payments', 'stock_transactions', 'stock_adjustments', 'damage_details', 'damages',
            'purchase_return_details', 'purchase_returns', 'sales_return_details', 'sales_returns', 'sale_details', 'sales',
            'purchase_details', 'purchases'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function documentColumns(Blueprint $table): void
    {
        $table->string('status', 20)->default('posted')->index();
        $table->string('cancel_reason')->nullable();
        $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('cancelled_at')->nullable();
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamps();
    }
};
