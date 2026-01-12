<?php

namespace Modules\Accounting\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property int $days_overdue
 * @property string|null $email_subject
 * @property string|null $email_body
 * @property bool $print_letter
 * @property bool $send_email
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 */
class DunningLevel extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'days_overdue',
        'email_subject',
        'email_body',
        'print_letter',
        'send_email',
    ];

    protected $casts = [
        'days_overdue' => 'integer',
        'print_letter' => 'boolean',
        'send_email' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
