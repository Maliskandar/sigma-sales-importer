<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Upload extends Model
{
    protected $fillable = [
        'batch_code', 'file_daily', 'file_mp', 'file_produk',
        'status', 'total_rows', 'processed_rows', 'success_rows',
        'error_rows', 'warning_rows', 'error_summary', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(UploadLog::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SalesTransaction::class);
    }

    public function errorLogs(): HasMany
    {
        return $this->hasMany(UploadLog::class)->where('level', 'error');
    }

    public function getProgressPercentageAttribute(): int
    {
        if ($this->total_rows === 0) return 0;
        return (int) round(($this->processed_rows / $this->total_rows) * 100);
    }
}
