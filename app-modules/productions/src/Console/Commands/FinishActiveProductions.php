<?php

namespace CorvMC\Productions\Console\Commands;

use Illuminate\Console\Command;
use CorvMC\Productions\Models\Production;
use CorvMC\Productions\Models\States\FinishedState;

class FinishActiveProductions extends Command
{
    protected $signature = 'productions:finish-active';
    protected $description = 'Set all active productions to finished status';

    public function handle()
    {
        $activeProductions = Production::where('status', 'active')->get();
        
        if ($activeProductions->isEmpty()) {
            $this->info('No active productions found.');
            return;
        }

        $count = 0;
        foreach ($activeProductions as $production) {
            try {
                $production->status->transitionTo(FinishedState::class, [
                    'ended_at' => now(),
                    'wrap_up_complete' => true,
                ]);
                $count++;
                $this->info("Finished production: {$production->title}");
            } catch (\Exception $e) {
                $this->error("Failed to finish production {$production->title}: {$e->getMessage()}");
            }
        }

        $this->info("Successfully finished {$count} production(s).");
    }
} 