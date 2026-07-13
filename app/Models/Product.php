<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $fillable = ['code', 'name', 'is_bundle', 'base_price', 'hpp'];

    protected function casts(): array
    {
        return [
            'is_bundle' => 'boolean',
            'base_price' => 'decimal:2',
            'hpp' => 'decimal:2',
        ];
    }

    public function bundleItems(): HasMany
    {
        return $this->hasMany(BundleItem::class, 'bundle_product_id');
    }

    /**
     * Komponen bundle, terurut sesuai sort_order untuk penulisan output.
     */
    public function components(): HasMany
    {
        return $this->hasMany(BundleItem::class, 'bundle_product_id')->orderBy('sort_order');
    }

    public function bundleProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'bundle_items', 'bundle_product_id', 'item_product_id')
            ->withPivot('quantity');
    }

    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function getPriceForPlatform(int $platformId): ?ProductPrice
    {
        return $this->productPrices()->where('platform_id', $platformId)->first();
    }
}
