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

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained();
            $table->string('name');
            $table->text('description');
            $table->integer('capacity')->nullable();
            $table->json('amenities')->default('[]');
            $table->json('hours')->default('[]');
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('room_id')->constrained();
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->string('state');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('rooms');
    }
};
