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
        Schema::table('posts', function (Blueprint $table) {
            $table
                ->bigInteger('mentionable_id')
                ->nullable()
                ->after('tags');
            $table
                ->string('mentionable_type')
                ->nullable()
                ->after('mentionable_id');
            $table
                ->jsonb('tags')
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
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('mentionable_id');
            $table->dropColumn('mentionable_type');
            $table->jsonb('tags')->change();
        });
    }
};
