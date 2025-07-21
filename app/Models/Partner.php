<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Partner
 *
 * @package App\Models
 *
 * @property int $id Primary Key, auto-increment.
 * @property int $company_id Foreign Key to companies.id.
 * @property string $name The partner's name.
 * @property string $type 'Customer', 'Vendor', or 'Both'.
 * @property string|null $contact_person Nullable string for contact person.
 * @property string|null $email Nullable string for email.
 * @property string|null $phone Nullable string for phone.
 * @property string|null $address_line_1 Nullable string for address line 1.
 * @property string|null $address_line_2 Nullable string for address line 2.
 * @property string|null $city Nullable string for city.
 * @property string|null $state Nullable string for state.
 * @property string|null $zip_code Nullable string for zip code.
 * @property string|null $country Nullable string for country.
 * @property string|null $tax_id Nullable string for tax identification number.
 * @property bool $is_active Boolean, default true.
 * @property \Illuminate\Support\Carbon|null $created_at Timestamp when the record was created.
 * @property \Illuminate\Support\Carbon|null $updated_at Timestamp when the record was last updated.
 * @property \Illuminate\Support\Carbon|null $deleted_at Timestamp when the record was soft-deleted.
 *
 * @property-read \App\Models\Company $company The company this partner belongs to.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Invoice[] $invoices The invoices associated with this partner as a customer.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VendorBill[] $vendorBills The vendor bills associated with this partner as a vendor.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Payment[] $payments The payments associated with this partner.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\JournalEntryLine[] $journalEntryLines The journal entry lines associated with this partner.
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
