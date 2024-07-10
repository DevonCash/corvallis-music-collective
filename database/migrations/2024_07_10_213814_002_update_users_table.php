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
            $table->dropColumn('bio');
            $table->dropColumn('links');
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
            $table
                ->text('bio')
                ->nullable()
                ->after('remember_token');
            $table
                ->jsonb('links')
                ->default('[]')
                ->nullable()
                ->after('bio');
        });
    }
};
