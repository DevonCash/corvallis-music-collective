<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponsor_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('amount', 10, 2);
            $table->text('benefits');
            $table->timestamps();
        });

        Schema::create('sponsors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('contact_email');
            $table->string('contact_phone');
            $table->string('website');
            $table->text('description');
            $table->foreignId('tier_id')->constrained('sponsor_tiers')->cascadeOnDelete();
            $table->timestamp('active_until');
            $table->timestamps();
        });

        Schema::create('sponsor_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->timestamps();

            $table->unique(['sponsor_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsor_users');
        Schema::dropIfExists('sponsors');
        Schema::dropIfExists('sponsor_tiers');
    }
}; 