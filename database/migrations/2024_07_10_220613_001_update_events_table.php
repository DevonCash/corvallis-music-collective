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
        Schema::table('events', function (Blueprint $table) {
            $table
                ->jsonb('poster')
                ->nullable()
                ->after('description');
            $table
                ->text('description')
                ->nullable()
                ->change();
            $table
                ->jsonb('links')
                ->default('[]')
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
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('poster');
            $table->text('description')->change();
            $table->jsonb('links')->change();
        });
    }
};
