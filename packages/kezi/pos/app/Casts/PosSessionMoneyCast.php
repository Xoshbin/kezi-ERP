<?php

namespace Kezi\Pos\Casts;

use Illuminate\Database\Eloquent\Model;
use Kezi\Foundation\Casts\BaseCurrencyMoneyCast;
use Kezi\Foundation\Models\Currency;

class PosSessionMoneyCast extends BaseCurrencyMoneyCast
{
    protected function resolveCurrency(Model $model): Currency
    {
        if (method_exists($model, 'profile')) {
            $profile = $model->relationLoaded('profile')
                ? $model->profile
                : ($model->getAttribute('pos_profile_id') ? $model->profile()->with('company.currency')->first() : null);

            if ($profile && $profile->company && $profile->company->currency) {
                return $profile->company->currency;
            }
        }

        return parent::resolveCurrency($model);
    }
}
