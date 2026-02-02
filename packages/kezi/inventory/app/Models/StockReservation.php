<?php

namespace Kezi\Inventory\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kezi\Product\Models\Product;

class StockReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'stock_move_id',
        'location_id',
        'quantity',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function move(): BelongsTo
    {
        return $this->belongsTo(StockMove::class, 'stock_move_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }
}
