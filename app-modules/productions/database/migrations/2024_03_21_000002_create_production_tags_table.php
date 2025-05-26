<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('production_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('type')->default('general'); // Can be 'genre', 'audience', or 'general'
            $table->timestamps();
        });

        Schema::create('production_tag', function (Blueprint $table) {
            $table->foreignId('production_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['production_id', 'production_tag_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('production_tag');
        Schema::dropIfExists('production_tags');
    }
}; 