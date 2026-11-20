<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestockOrder extends Model
{
    protected $fillable = [
        'product_id',
        'supplier_id',
        'qty_order',
        'status',
        'trigger_type',
        'is_synced',
        'synced_at',
    ];

    protected $casts = [
        'qty_order' => 'float',
        'is_synced' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
