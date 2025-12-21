@php use Modules\Foundation\Filament\Resources\Accounts\AccountResource; @endphp
@php use Modules\Foundation\Filament\Resources\Companies\CompanyResource; @endphp
@php use Modules\Foundation\Support\TranslatableHelper; @endphp
<div>
    @if(!empty($preview['issues']))
        <div class="p-3 mb-3 rounded bg-[var(--color-danger-50)] text-[var(--color-danger-800)]">
            <div class="font-semibold mb-1">{{ __('posting_preview.errors_title') }}</div>
            <ul class="list-disc pl-5 space-y-1">
                @foreach($preview['issues'] as $issue)
                    <li>
                        {{ is_array($issue['message'] ?? null) ? json_encode($issue['message']) : ($issue['message'] ?? '') }}
                        @php $t = $issue['type'] ?? ''; @endphp
                        @if(in_array($t, ['ar_account_missing','sales_discount_missing','tax_payable_missing','sales_journal_missing']))
                            <a href="{{ CompanyResource::getUrl('edit', ['record' => $adjustment->company_id]) }}"
                               class="ml-2 underline" target="_blank">Open Company</a>
                        @endif
                        @if($t === 'tax_payable_missing')
                            <a href="{{ AccountResource::getUrl() }}"
                               class="ml-2 underline" target="_blank">Open Accounts</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="overflow-auto max-h-[480px]">
        <table class="w-full text-sm">
            <thead>
            <tr class="border-b">
                <th class="text-left py-2 pr-2">Account</th>
                <th class="text-left py-2 pr-2">Description</th>
                <th class="text-right py-2 pr-2">Debit</th>
                <th class="text-right py-2 pr-2">Credit</th>
            </tr>
            </thead>
            <tbody>
            @foreach($preview['lines'] as $l)
                <tr class="border-b">
                    <td class="py-2 pr-2">
                        @php
                            $accCode = TranslatableHelper::getLocalizedValue($l['account_code'] ?? null);
                            $accName = TranslatableHelper::getLocalizedValue($l['account_name'] ?? null);
                            $label = trim($accCode . ' ' . $accName);
                        @endphp
                        @if(!empty($l['account_id']))
                            <a href="{{ AccountResource::getUrl('edit', ['record' => $l['account_id']]) }}"
                               class="underline" target="_blank">{{ $label }}</a>
                        @else
                            {{ $label }}
                        @endif
                    </td>
                    <td class="py-2 pr-2">
                        @php $desc = TranslatableHelper::getLocalizedValue($l['description'] ?? null); @endphp
                        {{ $desc }}
                    </td>
                    <td class="text-right py-2 pr-2">{{ number_format(($l['debit_minor'] ?? 0) / 100, 2) }}</td>
                    <td class="text-right py-2 pr-2">{{ number_format(($l['credit_minor'] ?? 0) / 100, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-3 flex items-center justify-between">
        <div>
            <a href="{{ AccountResource::getUrl('index') }}"
               class="underline text-gray-700" target="_blank">{{ __('posting_preview.links.open_accounts') }}</a>
            <a href="{{ CompanyResource::getUrl('edit', ['record' => $adjustment->company_id]) }}"
               class="underline text-gray-700 ml-4" target="_blank">{{ __('posting_preview.links.open_company') }}</a>
        </div>
        <div class="text-right">
            @php($totals = $preview['totals'])
            <div class="font-semibold">
                Totals —
                Debits: {{ number_format(($totals['debit_minor'] ?? 0) / 100, 2) }} |
                Credits: {{ number_format(($totals['credit_minor'] ?? 0) / 100, 2) }}
                @if(!empty($totals['balanced']))
                    <span class="text-[var(--color-success-700)]">(Balanced)</span>
                @else
                    <span class="text-[var(--color-danger-700)]">(Unbalanced)</span>
                @endif
            </div>
        </div>
    </div>
</div>

