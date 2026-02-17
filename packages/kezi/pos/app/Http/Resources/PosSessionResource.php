<?php

namespace Kezi\Pos\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Kezi\Pos\Models\PosSession;

/**
 * @mixin PosSession
 */
class PosSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pos_profile_id' => $this->pos_profile_id,
            'user_id' => $this->user_id,
            'opened_at' => $this->opened_at,
            'closed_at' => $this->closed_at,
            'status' => $this->status,
            'opening_cash' => $this->opening_cash ? $this->opening_cash->getAmount()->toFloat() : 0,
            'opening_cash_minor' => $this->opening_cash ? $this->opening_cash->getMinorAmount()->toInt() : 0,
            'closing_cash' => $this->closing_cash ? $this->closing_cash->getAmount()->toFloat() : 0,
            'closing_cash_minor' => $this->closing_cash ? $this->closing_cash->getMinorAmount()->toInt() : 0,
            'profile' => new PosProfileResource($this->whenLoaded('profile')),
        ];
    }
}
