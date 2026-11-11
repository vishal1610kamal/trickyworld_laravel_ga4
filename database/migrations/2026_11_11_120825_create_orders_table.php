<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('product');
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3);
            $table->string('status')->default('pending');
            // What the visitor's browser knew at checkout, kept for the server-side purchase event.
            $table->boolean('analytics_consent')->default(false);
            $table->string('ga_client_id', 50)->nullable();
            $table->string('ga_session_id', 30)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('ga_sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
