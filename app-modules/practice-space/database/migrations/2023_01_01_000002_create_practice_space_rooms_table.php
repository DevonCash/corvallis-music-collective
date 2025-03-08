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
        Schema::create('practice_space_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_category_id')->nullable()->constrained('practice_space_room_categories')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('capacity');
            $table->decimal('hourly_rate', 8, 2);
            $table->boolean('is_active')->default(true);
            $table->json('photos')->nullable();
            $table->json('specifications')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_space_rooms');
    }
}; 