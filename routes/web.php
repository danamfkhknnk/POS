<?php

use App\Http\Controllers\ReceiptController;
use App\Livewire\Auth\Login;
use App\Livewire\Cashier;
use App\Livewire\Cashier\Transactions;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $user = auth()->user();

    if (! $user) {
        return redirect()->route('login');
    }

    return redirect($user->getHomeUrl());
});

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/kasir', Cashier::class)->name('cashier.index');

    Route::get('/kasir/transactions', Transactions::class)->name('cashier.transactions');

    // Custom receipt print page: renders only the purchased items & totals.
    Route::get('/receipts/{trxId}/print', ReceiptController::class)->name('receipts.print');

    Route::post('/logout', function () {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});

// The /admin Filament panel itself is protected by User::canAccessPanel():
// only the admin role may enter, staff get a 403.
