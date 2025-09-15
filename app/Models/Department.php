<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * Class Department
 *
 * @property int $id
 * @property int $company_id
 * @property array<string, string> $name
 * @property string|null $description
 * @property int|null $parent_department_id
 * @property int|null $manager_id
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Department|null $parentDepartment
 * @property-read Collection<int, Department> $childDepartments
 * @property-read int|null $child_departments_count
 * @property-read Collection<int, Employee> $employees
 * @property-read int|null $employees_count
 * @property-read Collection<int, Position> $positions
 * @property-read int|null $positions_count
 * @property-read User|null $manager
 */
class Department extends Model
{
    use HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'parent_department_id',
        'manager_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public array $translatable = ['name'];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the translatable fields that should be searched.
     *
     * @return array<int, string>
     */
    public function getTranslatableSearchFields(): array
    {
        return ['name'];
    }

    /**
     * Get the non-translatable fields that should be searched.
     *
     * @return array<int, string>
     */
    public function getNonTranslatableSearchFields(): array
    {
        return ['description'];
    }

    /**
     * Get the company that owns the Department.
     */
    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the parent department.
     */
    /**
     * @return BelongsTo<Department, static>
     */
    public function parentDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_department_id');
    }

    /**
     * Get the child departments.
     */
    /**
     * @return HasMany<Department, static>
     */
    public function childDepartments(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_department_id');
    }

    /**
     * Get the manager of this department.
     */
    /**
     * @return BelongsTo<User, static>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the employees in this department.
     */
    /**
     * @return HasMany<Employee, static>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get the positions in this department.
     */
    /**
     * @return HasMany<Position, static>
     */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    /**
     * Get all descendants (child departments and their children recursively).
     *
     * @return Collection<int, Department>
     */
    public function getAllDescendants(): Collection
    {
        $descendants = new \Illuminate\Database\Eloquent\Collection;

        foreach ($this->childDepartments as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }

        return $descendants;
    }

    /**
     * Get all ancestors (parent departments up to the root).
     *
     * @return Collection<int, Department>
     */
    public function getAllAncestors(): Collection
    {
        $ancestors = new \Illuminate\Database\Eloquent\Collection;
        $current = $this->parentDepartment;

        while ($current) {
            $ancestors->push($current);
            $current = $current->parentDepartment;
        }

        return $ancestors;
    }

    /**
     * Check if this department is a root department (has no parent).
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_department_id);
    }

    /**
     * Check if this department is a leaf department (has no children).
     */
    public function isLeaf(): bool
    {
        return $this->childDepartments()->count() === 0;
    }

    /**
     * Get the total number of employees in this department and all its descendants.
     */
    public function getTotalEmployeeCount(): int
    {
        $count = $this->employees()->count();

        foreach ($this->childDepartments as $child) {
            $count += $child->getTotalEmployeeCount();
        }

        return $count;
    }
}
