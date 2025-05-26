<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('address');
            $table->integer('capacity')->nullable();
            $table->text('description')->nullable();
            $table->json('contact_info');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('venues');
    }
}; 