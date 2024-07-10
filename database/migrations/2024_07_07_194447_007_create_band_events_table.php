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
        Schema::create('band_events', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('band_id')->index();
            $table->bigInteger('event_id')->index();
            $table->boolean('featured');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('band_events');
    }
};
