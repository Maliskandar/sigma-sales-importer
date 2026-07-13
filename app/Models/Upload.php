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
        // Saat sudah selesai/gagal, proses dianggap 100% (baris kosong berformat
        // bisa membuat total_rows sedikit lebih besar dari processed_rows).
        if (in_array($this->status, ['completed', 'failed'], true)) {
            return 100;
        }
        if ($this->total_rows === 0) return 0;
        return min(100, (int) round(($this->processed_rows / $this->total_rows) * 100));
    }
}
