<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockQuant extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'location_id',
        'quantity',
        'reserved_quantity',
    ];

    protected $casts = [
        'quantity' => 'float',
        'reserved_quantity' => 'float',
    ];

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Product, static>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<StockLocation, static>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }
}

