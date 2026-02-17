<?php

namespace Kezi\Pos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosResource extends Model
{
    protected $fillable = [
        'pos_profile_id',
        'type',
        'name',
        'status',
        'layout_data',
    ];

    protected function casts(): array
    {
        return [
            'layout_data' => 'array',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PosProfile::class, 'pos_profile_id');
    }
}
