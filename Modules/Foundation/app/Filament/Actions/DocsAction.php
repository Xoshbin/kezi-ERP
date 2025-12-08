<?php

namespace Modules\Foundation\Filament\Actions;

use Filament\Actions\Action;
use Illuminate\Support\Str;

class DocsAction
{
    public static function make(string $slug): Action
    {
        // Map short slugs to full documentation paths
        $fullSlug = self::mapSlugToDocumentationPath($slug);

        // Generate a human-readable title from the original slug
        $title = Str::of($slug)
            ->replace('-', ' ')
            ->title()
            ->append(' ' . __('foundation::messages.guide'));

        // Generate the action name (slug + '_docs' to match test expectations)
        $actionName = $slug . '_docs';

        return Action::make($actionName)
            ->label($title)
            ->icon('heroicon-o-book-open')
            ->color('info')
            ->url(route('docs.show', ['slug' => $fullSlug]))
            ->openUrlInNewTab();
    }

    /**
     * Map short slugs to their full documentation paths
     */
    private static function mapSlugToDocumentationPath(string $slug): string
    {
        $mapping = [
            'payments' => 'User Guide/payments',
            'loan-agreements' => 'User Guide/loan-agreements',
            'customer-invoices' => 'User Guide/customer-invoices',
            'vendor-bills' => 'User Guide/vendor-bills',
            'bank-statements' => 'User Guide/bank-statements',
            'bank-reconciliation' => 'User Guide/bank-reconciliation',
            'stock-management' => 'User Guide/stock-management',
            'opening-balances' => 'User Guide/opening-balances',
            'payment-terms-guide' => 'User Guide/payment-terms-guide',
            'receipt-payment-vouchers' => 'User Guide/receipt-payment-vouchers',
        ];

        return $mapping[$slug] ?? $slug;
    }
}
