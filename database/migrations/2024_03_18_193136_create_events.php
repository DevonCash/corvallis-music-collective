<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("event_series", function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->text("description")->nullable();
            $table->string("recurrance")->nullable();
            $table->timestamp("start_at")->nullable();
            $table->timestamp("end_at")->nullable();
            $table->jsonb("prototype")->nullable();
            $table->timestamps();
        });
        Schema::create("events", function (Blueprint $table) {
            $table->id();
            $table->string("name")->nullable();
            $table->text("description")->nullable();
            $table->timestamp("start_at")->nullable();
            $table->timestamp("end_at")->nullable();
            $table->unsignedBigInteger("series_id")->nullable();
            $table->foreign("series_id")->references("id")->on("event_series");
            $table->string("location")->nullable();
            $table->string("url")->nullable();
            $table->string("image")->nullable();
            $table->timestamp("published_at")->nullable();
            $table->jsonb("links")->nullable();
            $table->jsonb("cost")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("events");
        Schema::dropIfExists("event_series");
    }
};
