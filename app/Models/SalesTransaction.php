<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesTransaction extends Model
{
    protected $fillable = [
        'upload_id', 'file_source', 'sale_date', 'group_code', 'kanal',
        'metode_bayar', 'toko', 'adv', 'type_transaksi', 'order_number',
        'awb', 'customer_phone', 'customer_name', 'billing_address',
        'provinsi', 'kabupaten', 'kecamatan', 'note', 'product_code',
        'quantity', 'unit_price', 'total_per_line', 'ekspedisi',
        'warehouse', 'status_order',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'unit_price' => 'decimal:2',
            'total_per_line' => 'decimal:2',
        ];
    }

    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }
}
