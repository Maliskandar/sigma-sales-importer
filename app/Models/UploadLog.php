<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UploadLog extends Model
{
    protected $fillable = ['upload_id', 'file_source', 'row_number', 'level', 'message', 'raw_data'];

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
        ];
    }

    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }
}
