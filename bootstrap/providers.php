<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use Illuminate\Broadcasting\BroadcastServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    BroadcastServiceProvider::class,
];
