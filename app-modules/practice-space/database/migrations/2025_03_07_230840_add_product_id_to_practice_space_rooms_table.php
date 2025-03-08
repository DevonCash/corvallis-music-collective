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
        Schema::table('practice_space_rooms', function (Blueprint $table) {
            // Add nullable foreign key to finance_products table
            // This will be nullable initially to allow for gradual integration
            $table->foreignId('product_id')->nullable()->after('room_category_id')
                ->comment('Reference to the finance module product');
                
            // We don't add a constraint here because the finance module might not be installed
            // The application logic will handle the relationship
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('practice_space_rooms', function (Blueprint $table) {
            $table->dropColumn('product_id');
        });
    }
};
