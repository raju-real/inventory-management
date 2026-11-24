<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('points')->default(0)->after('is_walk_in');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->integer('points_redeemed')->default(0)->after('vat_amount');
            $table->decimal('points_amount', 14, 2)->default(0)->after('points_redeemed');
            $table->integer('points_earned')->default(0)->after('points_amount');
            $table->integer('points_reversed')->default(0)->after('points_earned');
        });

        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->char('unique_id', 10)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('sales_return_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('type', 20);
            $table->integer('points');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('point_transactions');
        Schema::table('sales', fn (Blueprint $table) => $table->dropColumn(['points_redeemed', 'points_amount', 'points_earned', 'points_reversed']));
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn('points'));
    }
};
