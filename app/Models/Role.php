<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
