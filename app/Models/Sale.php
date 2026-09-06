<?php

namespace App\Models;

use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    /**
     * Generate a unique transaction ID shared by all lines of one checkout.
     */
    public static function generateTrxId(): string
    {
        do {
            $trxId = 'TRX-'.now()->format('ymd').'-'.strtoupper(bin2hex(random_bytes(4)));
        } while (self::query()->where('trx_id', $trxId)->exists());

        return $trxId;
    }

    protected $fillable = ['trx_id', 'product_id', 'outlet_id', 'user_id', 'quantity', 'unit_price', 'total', 'sold_at'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
            'sold_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * All sale lines belonging to the same checkout (transaction).
     */
    public function lines(): HasMany
    {
        return $this->hasMany(self::class, 'trx_id', 'trx_id');
    }
}
