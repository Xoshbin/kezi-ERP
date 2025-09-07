<?php

namespace App\Support\Filament;

use Filament\Actions\Action;

class DocsAction
{
    public static function make(string $slug, ?string $label = null): Action
    {
        $label ??= match ($slug) {
            'payments' => __('Payments Guide'),
            'loan-agreements' => __('Loan Agreements Guide'),
            default => __('Help / Docs'),
        };

        // Map short slugs to full paths for nested docs
        $fullSlug = match ($slug) {
            'payments' => 'User Guide/payments',
            'loan-agreements' => 'User Guide/loan-agreements',
            default => $slug,
        };

        return Action::make($slug . '_docs')
            ->label($label)
            ->icon('heroicon-o-question-mark-circle')
            ->color('gray')
            ->url(route('docs.show', ['slug' => $fullSlug]))
            ->openUrlInNewTab();
    }
}

