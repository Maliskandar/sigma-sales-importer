<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Platform extends Model
{
    protected $fillable = ['code', 'name', 'channel_type'];

    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }
}
