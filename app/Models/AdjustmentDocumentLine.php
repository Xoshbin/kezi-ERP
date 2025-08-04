<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Observers\AdjustmentDocumentLineObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([AdjustmentDocumentLineObserver::class])]
class AdjustmentDocumentLine extends Model
{
    use HasFactory;

    protected $table = 'adjustment_document_lines';

    protected $fillable = [
        'adjustment_document_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'tax_id',
        'subtotal',
        'total_line_tax',
        'account_id'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => MoneyCast::class,
        'subtotal' => MoneyCast::class,
        'total_line_tax' => MoneyCast::class,
    ];

    public function adjustmentDocument(): BelongsTo
    {
        return $this->belongsTo(AdjustmentDocument::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the line items for this adjustment document.
     * An adjustment document consists of multiple detail lines.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lines(): HasMany
    {
        return $this->hasMany(AdjustmentDocumentLine::class);
    }

    /**
     * Accessor to provide the currency_id to the MoneyCast.
     * This makes the model responsible for knowing its own currency context.
     */
    public function getCurrencyIdAttribute(): int
    {
        // This assumes the 'adjustmentDocument' relationship is always loaded when needed.
        // You can add loadMissing('adjustmentDocument') for robustness if necessary.
        return $this->adjustmentDocument->currency_id;
    }
}
