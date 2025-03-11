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
            if (!Schema::hasColumn('practice_space_bookings', 'payment_completed')) {
                $table->boolean('payment_completed')->default(false)->after('payment_status');
            }
            
            if (!Schema::hasColumn('practice_space_bookings', 'no_show_notes')) {
                $table->text('no_show_notes')->nullable()->after('cancellation_reason');
            }
            
            if (!Schema::hasColumn('practice_space_bookings', 'confirmation_requested_at')) {
                $table->timestamp('confirmation_requested_at')->nullable()->after('state');
            }
            
            if (!Schema::hasColumn('practice_space_bookings', 'confirmation_deadline')) {
                $table->timestamp('confirmation_deadline')->nullable()->after('confirmation_requested_at');
            }
            
            if (!Schema::hasColumn('practice_space_bookings', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('confirmation_deadline');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('practice_space_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'payment_completed',
                'no_show_notes',
                'confirmation_requested_at',
                'confirmation_deadline',
                'confirmed_at',
            ]);
        });
    }
}; 