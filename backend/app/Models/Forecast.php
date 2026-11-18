<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Forecast extends Model
{
    use HasFactory;

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $fillable = [
        'product_id',
        'period_month',
        'actual_qty',
        'forecast_qty',
        'mape_error',
    ];

    protected $casts = [
        'actual_qty' => 'float',
        'forecast_qty' => 'float',
        'mape_error' => 'float',
        'created_at' => 'datetime:d-m-y H:i:s',
        'updated_at' => 'datetime:d-m-y H:i:s', 
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}