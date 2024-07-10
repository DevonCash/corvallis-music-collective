<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("profile", function (Blueprint $table) {
            $table->id();
            $table->bigInteger("user_id");
            $table->string("name");
            $table->text("bio");
            $table->jsonb("links")->default("{}");
            $table->timestamp("created_at")->nullable();
            $table->timestamp("updated_at")->nullable();

            $table->bigInteger("postable_id");
            $table->string("postable_type");

            $table
                ->foreign("user_id")
                ->references("id")
                ->on("users")
                ->onDelete("cascade")
                ->onUpdate("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("profile");
    }
};
