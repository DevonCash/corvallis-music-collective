<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('practice_space_bookings', function (Blueprint $table) {
            $table->timestamp('confirmation_requested_at')->nullable()->after('state');
            $table->timestamp('confirmation_deadline')->nullable()->after('confirmation_requested_at');
            $table->timestamp('confirmed_at')->nullable()->after('confirmation_deadline');
            $table->timestamp('cancelled_at')->nullable()->after('confirmed_at');
            $table->string('cancellation_reason')->nullable()->after('cancelled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('practice_space_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'confirmation_requested_at',
                'confirmation_deadline',
                'confirmed_at',
                'cancelled_at',
                'cancellation_reason',
            ]);
        });
    }
}; 