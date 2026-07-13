<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BundleItem extends Model
{
    protected $fillable = [
        'bundle_product_id', 'item_product_id', 'sku', 'name',
        'quantity', 'sort_order', 'finance_price', 'marketing_price', 'hpp',
    ];

    protected function casts(): array
    {
        return [
            'finance_price' => 'decimal:2',
            'marketing_price' => 'decimal:2',
            'hpp' => 'decimal:2',
        ];
    }

    public function bundleProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'bundle_product_id');
    }

    public function itemProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_product_id');
    }
}
