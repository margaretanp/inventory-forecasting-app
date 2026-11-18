<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'transaction_date',
    ];

    protected $casts = [
        'quantity' => 'float',
        'transaction_date' => 'date:Y-m-d',
        'created_at' => 'datetime:d-m-y H:i:s',
        'updated_at' => 'datetime:d-m-y H:i:s',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}