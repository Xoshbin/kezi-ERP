<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Partner
 *
 * @package App\Models
 * @property int $id
 * @property int|null $company_id
 * @property string $name
 * @property string $type
 * @property string|null $contact_person
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address_line_1
 * @property string|null $address_line_2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip_code
 * @property string|null $country
 * @property string|null $tax_id
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Company|null $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JournalEntryLine> $journalEntryLines
 * @property-read int|null $journal_entry_lines_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VendorBill> $vendorBills
 * @property-read int|null $vendor_bills_count
 * @method static \Database\Factories\PartnerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereAddressLine1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereAddressLine2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereContactPerson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereTaxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereZipCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner withoutTrashed()
 * @mixin \Eloquent
 */
class Partner extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'type',
        'contact_person',
        'email',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'zip_code',
        'country',
        'tax_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the company that owns the Partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the invoices for the Partner (as a customer).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    /**
     * Get the vendor bills for the Partner (as a vendor).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vendorBills()
    {
        return $this->hasMany(VendorBill::class, 'vendor_id');
    }

    /**
     * Get the payments associated with the Partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payments()
    {
        // payments.paid_to_from_partner_id is FK to partners.id [5]
        return $this->hasMany(Payment::class, 'paid_to_from_partner_id');
    }

    /**
     * Get the journal entry lines for the Partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function journalEntryLines()
    {
        // journal_entry_lines.partner_id is Nullable FK to partners.id [6]
        return $this->hasMany(JournalEntryLine::class, 'partner_id');
    }
}
