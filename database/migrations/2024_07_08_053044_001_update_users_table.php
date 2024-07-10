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
        Schema::table('users', function (Blueprint $table) {
            $table
                ->string('name', 255)
                ->nullable()
                ->change();
            $table
                ->text('bio')
                ->nullable()
                ->change();
            $table
                ->jsonb('links')
                ->default('[]')
                ->nullable()
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 255)->change();
            $table->text('bio')->change();
            $table
                ->jsonb('links')
                ->default('[]')
                ->change();
        });
    }
};
