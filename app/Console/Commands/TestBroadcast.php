<?php

namespace App\Console\Commands;

use App\Events\StockSynchronized;
use Illuminate\Console\Command;

class TestBroadcast extends Command
{
    protected $signature = 'test:broadcast';

    protected $description = 'Smoke test for broadcasting - dispatches StockSynchronized event';

    public function handle(): int
    {
        $event = new StockSynchronized(
            branchId: 'JK01',
            productId: 'PROD001',
            quantity: 50,
        );

        broadcast($event);

        $this->info('StockSynchronized broadcasted. Check logs for payload.');

        return Command::SUCCESS;
    }
}
