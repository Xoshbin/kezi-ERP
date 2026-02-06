<?php

namespace Kezi\Foundation\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Sequence Model
 *
 * Manages atomic sequential number generation for different document types per company.
 * This ensures race-condition-free number assignment following accounting best practices.
 *
 * @property int $id
 * @property int $company_id
 * @property string $document_type
 * @property string $prefix
 * @property int $current_number
 * @property int $padding
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sequence newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sequence newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sequence query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sequence whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sequence whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sequence whereCurrentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sequence whereDocumentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sequence whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sequence wherePadding($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sequence wherePrefix($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sequence whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Sequence extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'document_type',
        'prefix',
        'current_number',
        'padding',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'current_number' => 'integer',
        'padding' => 'integer',
    ];

    /**
     * Get the company that owns the sequence.
     */
    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Generate the next number in the sequence atomically.
     *
     * This method uses database-level atomic operations to prevent race conditions.
     *
     * @return string The formatted next number (e.g., "INV-00001")
     */
    public function getNextNumber(): string
    {
        return \Illuminate\Support\Facades\DB::transaction(function () {
            // Lock the row to ensure exclusive access
            /** @var Sequence $sequence */
            $sequence = static::where('id', $this->id)->lockForUpdate()->first();

            // Increment locally and save
            $sequence->current_number++;
            $sequence->save();

            // Format the number with prefix and padding
            return $sequence->prefix.'-'.str_pad((string) $sequence->current_number, $sequence->padding, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Get or create a sequence for a specific company and document type.
     */
    public static function getOrCreateSequence(
        int $companyId,
        string $documentType,
        string $prefix,
        int $padding = 5,
    ): static {
        /** @var static $sequence */
        $sequence = static::firstOrCreate(
            [
                'company_id' => $companyId,
                'document_type' => $documentType,
            ],
            [
                'prefix' => $prefix,
                'current_number' => 0,
                'padding' => $padding,
            ]
        );

        return $sequence;
    }
}
