<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->json('social_links')->nullable();
            $table->json('contact_info')->nullable();
            $table->timestamps();
        });

        Schema::create('production_act', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained()->cascadeOnDelete();
            $table->foreignId('act_id')->constrained()->cascadeOnDelete();
            $table->integer('order')->default(0);
            $table->integer('set_length')->nullable()->comment('Set length in minutes');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['production_id', 'act_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_act');
        Schema::dropIfExists('acts');
    }
}; 