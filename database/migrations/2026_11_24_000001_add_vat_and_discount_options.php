<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('vat_type', 10)->default('percentage')->after('sales_price');
            $table->decimal('vat_value', 14, 2)->default(0)->after('vat_type');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_walk_in')->default(false)->after('opening_balance');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->string('discount_type', 10)->default('percentage')->after('subtotal');
            $table->decimal('discount_value', 14, 2)->default(0)->after('discount_type');
            $table->string('vat_mode', 10)->default('product')->after('discount');
            $table->string('vat_type', 10)->nullable()->after('vat_mode');
            $table->decimal('vat_value', 14, 2)->default(0)->after('vat_type');
            $table->decimal('vat_amount', 14, 2)->default(0)->after('vat_value');
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->string('vat_type', 10)->default('percentage')->after('total');
            $table->decimal('vat_value', 14, 2)->default(0)->after('vat_type');
            $table->decimal('vat_amount', 14, 2)->default(0)->after('vat_value');
        });

        // Earlier sales stored their discount as an amount.
        DB::table('sales')->update(['discount_type' => 'flat', 'discount_value' => DB::raw('discount')]);
        DB::table('customers')->where('name', 'Walk-in Customer')->update(['is_walk_in' => true]);
    }

    public function down()
    {
        Schema::table('sale_details', fn (Blueprint $table) => $table->dropColumn(['vat_type', 'vat_value', 'vat_amount']));
        Schema::table('sales', fn (Blueprint $table) => $table->dropColumn(['discount_type', 'discount_value', 'vat_mode', 'vat_type', 'vat_value', 'vat_amount']));
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn('is_walk_in'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['vat_type', 'vat_value']));
    }
};
