<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    /**
     * The table associated with the model.
     * While Laravel's convention often matches the model name, explicitly
     * defining the table name ensures clarity and prevents potential issues
     * with pluralization rules.
     *
     * @var string
     */
    protected $table = 'audit_logs';

    /**
     * The attributes that are mass assignable.
     * For audit logs, it's crucial to capture all relevant details.
     * We explicitly list the fields defined in the migration and schema
     * that users or the system will set when creating an audit entry.
     * The 'id', 'created_at', and 'updated_at' fields are typically
     * managed automatically by Eloquent and thus excluded from $fillable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'event_type',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'description',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     * Eloquent's attribute casting is highly beneficial here.
     * `old_values` and `new_values` are stored as JSON/Text [1],
     * so casting them to `array` or `json` (which effectively does the same)
     * ensures they are automatically deserialized into PHP arrays when accessed,
     * and re-serialized into JSON when saved, simplifying data handling.
     * Timestamps (`created_at`, `updated_at`) are automatically cast to Carbon instances [9].
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that initiated the audit log.
     * The `user_id` column in the `audit_logs` table acts as a foreign key
     * to the `users` table, identifying the individual responsible for the action [1].
     * This establishes a `belongsTo` relationship to the `User` model [10].
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the auditable model that the audit log belongs to.
     * The `auditable_type` and `auditable_id` columns facilitate a
     * polymorphic relationship, allowing a single `audit_log` entry to
     * reference different types of models (e.g., `Invoice`, `Account`) [1, 11].
     * This is critical for flexible and comprehensive logging across various entities.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
