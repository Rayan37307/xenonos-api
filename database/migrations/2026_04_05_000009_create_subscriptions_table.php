<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('plan_name');
            $table->decimal('amount', 12, 2);
            $table->string('billing_cycle'); // monthly, quarterly, yearly
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status'); // active, cancelled, expired, past_due
            $table->string('payment_method')->nullable();
            $table->string('external_subscription_id')->nullable();
            $table->text('features')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
