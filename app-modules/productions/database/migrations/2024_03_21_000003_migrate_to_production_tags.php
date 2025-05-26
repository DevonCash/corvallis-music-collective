<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use CorvMC\Productions\Models\Production;
use CorvMC\Productions\Models\ProductionTag;

return new class extends Migration
{
    public function up()
    {
        // First, migrate existing data
        $productions = Production::all();
        
        foreach ($productions as $production) {
            // Migrate genre
            if ($production->genre) {
                $tag = ProductionTag::firstOrCreate(
                    ['name' => $production->genre],
                    ['type' => 'genre']
                );
                $production->tags()->attach($tag->id);
            }
            
            // Migrate target audience
            if ($production->target_audience) {
                $tag = ProductionTag::firstOrCreate(
                    ['name' => $production->target_audience],
                    ['type' => 'audience']
                );
                $production->tags()->attach($tag->id);
            }
        }

        // Then remove the old columns
        Schema::table('productions', function (Blueprint $table) {
            $table->dropColumn(['genre', 'target_audience']);
        });
    }

    public function down()
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->string('genre')->nullable();
            $table->string('target_audience')->nullable();
        });

        // Note: We don't restore the data in the down migration as it would be complex
        // to determine which tags were originally genre vs target audience
    }
}; 