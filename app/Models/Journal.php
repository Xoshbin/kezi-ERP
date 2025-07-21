<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Journal extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'journals';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'type',
        'short_code',
        'currency_id',
    ];

    /**
     * Get the company that owns the journal.
     * A journal belongs to a specific company in a multi-company setup [3].
     *
     * @OA\Property(
     *     property="company",
     *     type="object",
     *     ref="#/components/schemas/Company"
     * )
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the currency associated with the journal.
     * A journal can operate in a specific currency [3].
     *
     * @OA\Property(
     *     property="currency",
     *     type="object",
     *     ref="#/components/schemas/Currency"
     * )
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the journal entries for the journal.
     * A journal can have many journal entries [3].
     *
     * @OA\Property(
     *     property="journal_entries",
     *     type="array",
     *     @OA\Items(ref="#/components/schemas/JournalEntry")
     * )
     */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    /**
     * Define a unique scope for short_code within a company.
     * This is crucial to prevent duplicate short codes for journals within the same company [3].
     * Although this is typically enforced at the database migration level,
     * adding a method here can be useful for specific query needs if required later.
     */
    public function scopeUniqueShortCode(
        \Illuminate\Database\Eloquent\Builder $query,
        string $shortCode,
        int $companyId
    ): \Illuminate\Database\Eloquent\Builder {
        return $query->where('short_code', $shortCode)->where('company_id', $companyId);
    }
}
