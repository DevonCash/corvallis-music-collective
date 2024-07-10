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
            $table->string('name')->after('id');
            $table->text('bio')->after('name');
            $table
                ->jsonb('links')
                ->default('[]')
                ->after('bio');
            $table
                ->timestamp('published_at')
                ->nullable()
                ->after('admin');
            $table->dropColumn('admin');
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
            $table->dropColumn('name');
            $table->dropColumn('bio');
            $table->dropColumn('links');
            $table->dropColumn('published_at');
            $table->boolean('admin')->after('remember_token');
        });
    }
};
