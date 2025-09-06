<?php

namespace App\Support\Filament;

use Filament\Actions\Action;

class DocsAction
{
    public static function make(string $slug, ?string $label = null): Action
    {
        $label ??= match ($slug) {
            'payments' => __('Payments Guide'),
            default => __('Help / Docs'),
        };

        return Action::make($slug . '_docs')
            ->label($label)
            ->icon('heroicon-o-question-mark-circle')
            ->color('gray')
            ->url(route('docs.show', ['slug' => $slug]))
            ->openUrlInNewTab();
    }
}

