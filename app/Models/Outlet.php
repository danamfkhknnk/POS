<?php

namespace App\Models;

use Database\Factories\OutletFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Outlet extends Model
{
    /** @use HasFactory<OutletFactory> */
    use HasFactory;

    protected $fillable = ['name', 'code', 'address'];

    public function staff(): HasMany
    {
        return $this->hasMany(User::class, 'outlet_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'stocks')
            ->withPivot('quantity', 'min_stock')
            ->withTimestamps();
    }
}
