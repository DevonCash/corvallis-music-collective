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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->json('prices');
            $table->string('stripe_product_id')->nullable()->unique();
            $table->boolean('is_visible')->default(false);
            $table->string('subscription_interval')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->string('stripe_payment_intent_id')->unique();
            $table->integer('amount');
            $table->string('method');
            $table->string('state');
            $table->string('payable_type');
            $table->unsignedBigInteger('payable_id');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('products');
    }
};
