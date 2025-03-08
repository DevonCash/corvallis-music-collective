<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration converts the amount column from dollars (decimal) to cents (integer).
     */
    public function up(): void
    {
        // First, convert existing decimal amounts to cents and store in a temporary column
        Schema::table('finance_payments', function (Blueprint $table) {
            $table->integer('amount_cents')->nullable();
        });
        
        // Convert existing dollar amounts to cents
        DB::statement('UPDATE finance_payments SET amount_cents = ROUND(amount * 100)');
        
        // Now drop the original decimal column and rename the new one
        Schema::table('finance_payments', function (Blueprint $table) {
            $table->dropColumn('amount');
        });
        
        Schema::table('finance_payments', function (Blueprint $table) {
            $table->renameColumn('amount_cents', 'amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert back to dollars (decimal)
        Schema::table('finance_payments', function (Blueprint $table) {
            $table->decimal('amount_dollars', 10, 2)->nullable();
        });
        
        // Convert integer cents back to dollars
        DB::statement('UPDATE finance_payments SET amount_dollars = amount / 100.0');
        
        // Drop the integer column and rename the decimal one back to amount
        Schema::table('finance_payments', function (Blueprint $table) {
            $table->dropColumn('amount');
        });
        
        Schema::table('finance_payments', function (Blueprint $table) {
            $table->renameColumn('amount_dollars', 'amount');
        });
    }
};
