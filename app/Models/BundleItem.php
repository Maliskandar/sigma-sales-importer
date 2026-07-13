<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BundleItem extends Model
{
    protected $fillable = ['bundle_product_id', 'item_product_id', 'quantity'];

    public function bundleProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'bundle_product_id');
    }

    public function itemProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_product_id');
    }
}
