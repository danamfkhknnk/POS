<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue;

    public function __construct(
        public string $message
    ) {}

    public function handle(): void
    {
        Log::info('Processing notification: '.$this->message);
    }
}
