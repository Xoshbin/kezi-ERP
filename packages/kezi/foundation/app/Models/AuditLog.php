<?php

namespace Kezi\Foundation\Models;

use App\Models\Company;
use App\Models\User;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Kezi\Foundation\Database\Factories\AuditLogFactory;

/**
 * @property int $id
 * @property int $user_id
 * @property string $event_type
 * @property string $auditable_type
 * @property int $auditable_id
 * @property array<array-key, mixed>|null $old_values
 * @property array<array-key, mixed>|null $new_values
 * @property string|null $description
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $created_at
 * @property-read Model|Eloquent $auditable
 * @property-read User $user
 *
 * @method static Builder<static>|AuditLog newModelQuery()
 * @method static Builder<static>|AuditLog newQuery()
 * @method static Builder<static>|AuditLog query()
 * @method static Builder<static>|AuditLog whereAuditableId($value)
 * @method static Builder<static>|AuditLog whereAuditableType($value)
 * @method static Builder<static>|AuditLog whereCreatedAt($value)
 * @method static Builder<static>|AuditLog whereDescription($value)
 * @method static Builder<static>|AuditLog whereEventType($value)
 * @method static Builder<static>|AuditLog whereId($value)
 * @method static Builder<static>|AuditLog whereIpAddress($value)
 * @method static Builder<static>|AuditLog whereNewValues($value)
 * @method static Builder<static>|AuditLog whereOldValues($value)
 * @method static Builder<static>|AuditLog whereUserAgent($value)
 * @method static Builder<static>|AuditLog whereUserId($value)
 *
 * @property int|null $company_id
 * @property-read Company|null $company
 *
 * @method static Builder<static>|AuditLog whereCompanyId($value)
 * @method static \Kezi\Foundation\Database\Factories\AuditLogFactory factory($count = null, $state = [])
 *
 * @mixin Eloquent
 */
class AuditLog extends Model
{
    /** @use HasFactory<\Kezi\Foundation\Database\Factories\AuditLogFactory> */
    use HasFactory;

    protected static function newFactory(): AuditLogFactory
    {
        return AuditLogFactory::new();
    }

    /**
     * The name of the "updated at" column.
     * Set to null to disable `updated_at` functionality for this model.
     * An audit log is write-once, so it should never be "updated".
     *
     * @var string|null
     */
    public const UPDATED_AT = null; // <-- Add this line

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
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'company_id',
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
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Get the user that initiated the audit log.
     * The `user_id` column in the `audit_logs` table acts as a foreign key
     * to the `users` table, identifying the individual responsible for the action [1].
     * This establishes a `belongsTo` relationship to the `User` model [10].
     */
    /**
     * @return BelongsTo<User, static>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company that this audit log belongs to.
     * Audit logs should be company-specific for proper accounting audit trails.
     */
    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the auditable model that the audit log belongs to.
     * The `auditable_type` and `auditable_id` columns facilitate a
     * polymorphic relationship, allowing a single `audit_log` entry to
     * reference different types of models (e.g., `Invoice`, `Account`) [1, 11].
     * This is critical for flexible and comprehensive logging across various entities.
     */
    /**
     * @return MorphTo<Model, static>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
