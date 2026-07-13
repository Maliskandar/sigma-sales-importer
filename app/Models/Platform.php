<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Platform extends Model
{
    protected $fillable = ['code', 'name', 'channel_type', 'output_label', 'payment_label', 'aliases'];

    protected function casts(): array
    {
        return [
            'aliases' => 'array',
        ];
    }

    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    /**
     * Resolve the platform for a raw "Kanal" value from Excel by matching
     * the platform code or any of its configured aliases (case-insensitive).
     */
    public static function resolveByKanal(?string $kanal): ?self
    {
        if ($kanal === null || trim($kanal) === '') {
            return null;
        }

        $needle = strtoupper(trim($kanal));

        foreach (self::all() as $platform) {
            if (strtoupper($platform->code) === $needle) {
                return $platform;
            }
            foreach ($platform->aliases ?? [] as $alias) {
                if (strtoupper(trim($alias)) === $needle) {
                    return $platform;
                }
            }
        }

        return null;
    }
}
