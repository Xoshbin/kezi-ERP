<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
 */
class Sequence extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
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
        // Use atomic increment to prevent race conditions
        $this->increment('current_number');

        // Reload to get the updated value
        $this->refresh();

        // Format the number with prefix and padding
        return $this->prefix . '-' . str_pad($this->current_number, $this->padding, '0', STR_PAD_LEFT);
    }

    /**
     * Get or create a sequence for a specific company and document type.
     *
     * @param int $companyId
     * @param string $documentType
     * @param string $prefix
     * @param int $padding
     * @return static
     */
    public static function getOrCreateSequence(
        int $companyId,
        string $documentType,
        string $prefix,
        int $padding = 5
    ): static {
        return static::firstOrCreate(
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
    }
}
