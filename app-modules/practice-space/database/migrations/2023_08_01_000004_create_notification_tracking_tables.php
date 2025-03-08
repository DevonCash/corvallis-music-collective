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
        // We're using the activity log instead of these tables,
        // but we'll create the migration for completeness
        
        // This migration is intentionally empty as we're using the activity log
        // for tracking notifications instead of separate tables
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to reverse
    }
}; 