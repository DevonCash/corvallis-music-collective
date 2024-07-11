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
                ->string('nickname')
                ->nullable()
                ->after('name');
            $table
                ->text('bio')
                ->nullable()
                ->after('remember_token');
            $table
                ->jsonb('links')
                ->nullable()
                ->after('bio');
            $table
                ->jsonb('skills')
                ->nullable()
                ->after('links');
            $table->string('name', 255)->change();
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
            $table->dropColumn('nickname');
            $table->dropColumn('bio');
            $table->dropColumn('links');
            $table->dropColumn('skills');
            $table
                ->string('name', 255)
                ->nullable()
                ->change();
        });
    }
};
