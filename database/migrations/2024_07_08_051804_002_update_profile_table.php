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
        Schema::table('profile', function (Blueprint $table) {
            $table
                ->timestamp('published_at')
                ->nullable()
                ->after('links');
            $table->dropColumn('postable_id');
            $table->dropColumn('postable_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('profile', function (Blueprint $table) {
            $table->dropColumn('published_at');
            $table->bigInteger('postable_id')->after('created_at');
            $table->string('postable_type', 255)->after('updated_at');
        });
    }
};
