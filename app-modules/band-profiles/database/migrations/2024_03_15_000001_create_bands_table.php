<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('formation_date');
            $table->string('genre');
            $table->string('location');
            $table->text('bio');
            $table->timestamps();
        });

        Schema::create('band_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('band_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->timestamps();

            $table->unique(['band_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('band_members');
        Schema::dropIfExists('bands');
    }
}; 