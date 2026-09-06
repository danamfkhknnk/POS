<?php

namespace App\Console\Commands;

use App\Events\StockSynchronized;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

class SmokeTestBroadcast2 extends Command
{
    protected $signature = 'test:broadcast2';

    protected $description = 'Direct broadcast test with logging';

    public function handle(): int
    {
        Log::info('Starting broadcast test...');

        $event = new StockSynchronized('1', '1', 50);

        $channels = $event->broadcastOn();
        $payload = $event->broadcastWith();

        Log::info('Channels: '.json_encode($channels));
        Log::info('Payload: '.json_encode($payload));

        // Direct broadcast
        $result = Broadcast::connection()->broadcast(
            $channels,
            get_class($event),
            $payload
        );

        Log::info('Broadcast result: '.($result ? 'success' : 'failure'));

        $this->info('Check laravel.log for broadcast output.');

        return Command::SUCCESS;
    }
}
