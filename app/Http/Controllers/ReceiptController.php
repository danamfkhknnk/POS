<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;

class ReceiptController
{
    /**
     * Render a printable receipt containing only the purchased items and totals.
     */
    public function __invoke(Request $request, string $trxId)
    {
        $user = $request->user();

        /** @var Sale|null $trx */
        $trx = Sale::query()
            ->where('trx_id', $trxId)
            ->with(['outlet', 'user', 'lines.product'])
            ->first();

        abort_unless($trx !== null, 404);

        // Staff may only print receipts of their own outlet; admins may print any.
        if ($user->hasRole('staff') && $trx->outlet_id !== $user->outlet_id) {
            abort(403);
        }

        return view('receipts.print', [
            'trx' => $trx,
        ]);
    }
}
