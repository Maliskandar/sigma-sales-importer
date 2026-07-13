<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColumnMapping extends Model
{
    protected $fillable = ['file_type', 'excel_column', 'db_column', 'is_required', 'default_value'];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
        ];
    }

    /**
     * Get mapping for a specific file type as [excel_column => db_column]
     */
    public static function getMappingFor(string $fileType): array
    {
        return self::where('file_type', $fileType)
            ->pluck('db_column', 'excel_column')
            ->toArray();
    }

    /**
     * Get required columns for a specific file type
     */
    public static function getRequiredColumns(string $fileType): array
    {
        return self::where('file_type', $fileType)
            ->where('is_required', true)
            ->pluck('excel_column')
            ->toArray();
    }
}
