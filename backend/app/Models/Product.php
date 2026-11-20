<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'sku',
        'name',
        'category',
        'unit',
        'current_stock',
        'lead_time_days',
        'purchase_price',
        'optimal_alpha',
        'safety_stock',
        'reorder_point',
        'status',
    ];

    protected $casts = [
        'current_stock' => 'float',
        'lead_time_days' => 'integer',
        'purchase_price' => 'float',
        'optimal_alpha' => 'float',
        'safety_stock' => 'float',
        'reorder_point' => 'float',
        'created_at' => 'datetime:d-m-y H:i:s',
        'updated_at' => 'datetime:d-m-y H:i:s',
    ];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function forecasts(): HasMany
    {
        return $this->hasMany(Forecast::class);
    }

    public function latestRecommendation(): HasOne
    {
        return $this->hasOne(RestockRecommendation::class)->latestOfMany();
    }

    public function restockOrders(): HasMany
    {
        return $this->hasMany(RestockOrder::class);
    }
}
