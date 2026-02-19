<?php

namespace Kezi\Pos\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'settings' => $this->resource->settings,
            'features' => $this->resource->features,
            'default_income_account_id' => $this->resource->default_income_account_id,
            'default_payment_journal_id' => $this->resource->default_payment_journal_id,
        ];
    }
}
