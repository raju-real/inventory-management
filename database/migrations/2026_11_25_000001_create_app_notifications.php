<?php

use App\Services\NotificationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Stock alerts shown under the bell. "app_" keeps them apart from Laravel's own notifications table.
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->char('unique_id', 10)->unique();
            $table->string('type', 20); // low_stock, out_of_stock
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('message');
            $table->timestamp('resolved_at')->nullable(); // stock back above the minimum
            $table->timestamps();
            $table->index(['product_id', 'resolved_at']);
        });

        // One row per user who has seen a notification. No row means unseen.
        Schema::create('app_notification_reads', function (Blueprint $table) {
            $table->id();
            $table->char('unique_id', 10)->unique();
            $table->foreignId('app_notification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->unique(['app_notification_id', 'user_id']);
        });

        // Products that are already low or out of stock start with an unseen alert.
        app(NotificationService::class)->alertCurrentLowStock();
    }

    public function down()
    {
        Schema::dropIfExists('app_notification_reads');
        Schema::dropIfExists('app_notifications');
    }
};
