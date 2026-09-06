<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('outlets.{outletId}', function ($user, string $outletId) {
    if ($user->roles()->where('name', 'admin')->exists()) {
        return true;
    }

    return $user->outlet_id && (string) $user->outlet_id === $outletId;
});
