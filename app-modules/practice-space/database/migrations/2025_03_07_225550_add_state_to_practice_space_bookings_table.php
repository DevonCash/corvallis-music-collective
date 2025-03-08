<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('practice_space_bookings', function (Blueprint $table) {
            $table->string('state')->default('scheduled')->after('status');
        });
        
        // Copy status values to state for existing records
        DB::statement('UPDATE practice_space_bookings SET state = status');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('practice_space_bookings', function (Blueprint $table) {
            $table->dropColumn('state');
        });
    }
};
