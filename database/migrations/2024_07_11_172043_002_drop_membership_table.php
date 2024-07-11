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
        Schema::dropIfExists('membership');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('membership', function (Blueprint $table) {
            $table->bigInteger('user_id')->index();
            $table->text('bio')->nullable();
            $table->jsonb('links')->nullable();
            $table->jsonb('instruments')->nullable();
            $table
                ->enum('status', ['active', 'expired', 'inactive'])
                ->default('inactive');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->id();

            $table
                ->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }
};
