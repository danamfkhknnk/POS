<?php

namespace App\Console\Commands;

use App\Jobs\ProcessNotification;
use Illuminate\Console\Command;

class TestQueue extends Command
{
    protected $signature = 'test:queue';

    protected $description = 'Test queue with database driver - dispatch a test job';

    public function handle(): int
    {
        ProcessNotification::dispatch('Test queue message from command');

        $this->info('Job dispatched. Start queue worker with: php artisan queue:work');
        $this->info('Then check storage/logs/laravel.log for "Processing notification"');

        return Command::SUCCESS;
    }
}
