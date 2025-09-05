<?php

namespace App\Models;

use App\Casts\SalaryCurrencyMoneyCast;
use App\Traits\TranslatableSearch;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * Class Position
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $department_id
 * @property array<string, string> $title
 * @property string|null $description
 * @property array<string, mixed>|null $requirements
 * @property array<string, mixed>|null $responsibilities
 * @property string $employment_type
 * @property string $level
 * @property Money|null $min_salary
 * @property Money|null $max_salary
 * @property int|null $salary_currency_id
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Department|null $department
 * @property-read Currency|null $salaryCurrency
 * @property-read Collection<int, Employee> $employees
 * @property-read int|null $employees_count
 * @property-read Collection<int, EmploymentContract> $employmentContracts
 * @property-read int|null $employment_contracts_count
 */
class Position extends Model
{
    use HasFactory, HasTranslations;
    use TranslatableSearch;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'department_id',
        'title',
        'description',
        'requirements',
        'responsibilities',
        'employment_type',
        'level',
        'min_salary',
        'max_salary',
        'salary_currency_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'title' => 'array',
        'requirements' => 'array',
        'responsibilities' => 'array',
        'min_salary' => SalaryCurrencyMoneyCast::class,
        'max_salary' => SalaryCurrencyMoneyCast::class,
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public array $translatable = ['title'];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'employment_type' => 'full_time',
        'level' => 'entry',
        'is_active' => true,
    ];

    /**
     * Get the translatable fields that should be searched.
     *
     * @return array<int, string>
     */
    public function getTranslatableSearchFields(): array
    {
        return ['title'];
    }

    /**
     * Get the non-translatable fields that should be searched.
     *
     * @return array<int, string>
     */
    public function getNonTranslatableSearchFields(): array
    {
        return ['description', 'employment_type', 'level'];
    }

    /**
     * Get the company that owns the Position.
     */
    /**

     * @return BelongsTo<Company, static>

     */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the department this position belongs to.
     */
    /**

     * @return BelongsTo<Department, static>

     */

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the salary currency for this position.
     */
    /**

     * @return BelongsTo<Currency, static>

     */

    public function salaryCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'salary_currency_id');
    }

    /**
     * Get the employees in this position.
     */
    /**

     * @return HasMany<Employee, static>

     */

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get the employment contracts for this position.
     */
    /**

     * @return HasMany<EmploymentContract, static>

     */

    public function employmentContracts(): HasMany
    {
        return $this->hasMany(EmploymentContract::class);
    }

    /**
     * Get the salary range as a formatted string.
     */
    public function getSalaryRangeAttribute(): ?string
    {
        if (! $this->min_salary && ! $this->max_salary) {
            return null;
        }

        if (!$this->salaryCurrency) {
            throw new \RuntimeException('Position salary currency not found');
        }

        if ($this->min_salary && $this->max_salary) {
            return $this->min_salary->formatTo($this->salaryCurrency->code).' - '.
                   $this->max_salary->formatTo($this->salaryCurrency->code);
        }

        if ($this->min_salary) {
            return 'From '.$this->min_salary->formatTo($this->salaryCurrency->code);
        }

        if (!$this->max_salary) {
            throw new \RuntimeException('Position max salary not found');
        }

        return 'Up to '.$this->max_salary->formatTo($this->salaryCurrency->code);
    }

    /**
     * Check if a salary amount is within the position's range.
     */
    public function isSalaryInRange(Money $salary): bool
    {
        if ($this->min_salary && $salary->isLessThan($this->min_salary)) {
            return false;
        }

        if ($this->max_salary && $salary->isGreaterThan($this->max_salary)) {
            return false;
        }

        return true;
    }

    /**
     * Get the number of active employees in this position.
     */
    public function getActiveEmployeeCount(): int
    {
        return $this->employees()->where('employment_status', 'active')->count();
    }

    /**
     * Get the number of open positions (max employees - current employees).
     */
    public function getOpenPositions(int $maxEmployees = 1): int
    {
        return max(0, $maxEmployees - $this->getActiveEmployeeCount());
    }

    /**
     * Check if this position has any active employees.
     */
    public function hasActiveEmployees(): bool
    {
        return $this->getActiveEmployeeCount() > 0;
    }
}
