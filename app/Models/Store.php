<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Store extends Model
{
    protected $fillable = ['code', 'platform_id', 'admin_name', 'default_advertiser'];

    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }
}
