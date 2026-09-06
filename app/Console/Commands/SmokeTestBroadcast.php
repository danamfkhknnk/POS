<?php

namespace App\Console\Commands;

use App\Events\StockSynchronized;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class SmokeTestBroadcast extends Command
{
    protected $signature = 'test:broadcast-smoke';

    protected $description = 'Smoke test: dispatch event and process queue synchronously';

    public function handle(): int
    {
        $this->info('Creating StockSynchronized event...');
        $event = new StockSynchronized('1', '1', 50);

        $this->info('Creating BroadcastEvent job...');
        $job = new BroadcastEvent($event);

        $this->info('Pushing to queue (sync)...');
        Queue::push($job);

        $this->info('Done! Check laravel.log for broadcast output.');

        return Command::SUCCESS;
    }
}
