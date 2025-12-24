<!doctype html>
<html>
<head>
    <meta charset="utf-8"/>
    @php
        use Filament\Support\Facades\FilamentColor;
        use Filament\Support\Colors\Color as FsColor;
        use Modules\Foundation\Support\TranslatableHelper;
        $gray = FilamentColor::getColor('gray');
        $danger = FilamentColor::getColor('danger');
        $g100 = FsColor::convertToRgb($gray[100]);
        $g200 = FsColor::convertToRgb($gray[200]);
        $d700 = FsColor::convertToRgb($danger[700]);
    @endphp
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid {{ $g200 }}; padding: 4px 6px; }
        th { background: {{ $g100 }}; text-align: left; }
        tfoot td { font-weight: bold; }
        .errors { color: {{ $d700 }}; }
    </style>
</head>
<body>
<h2>{{ __('accounting::bill.posting_preview.pdf.vendor_bill_heading') }}</h2>
<p>Vendor Bill: {{ $bill->bill_reference ?? ('DRAFT-' . str_pad($bill->id, 5, '0', STR_PAD_LEFT)) }} | Company: {{ $bill->company->name }}</p>

@if(!empty($preview['errors']))
    <div class="errors">
        <strong>{{ __('accounting::bill.posting_preview.errors_title') }}:</strong>
        <ul>
            @foreach($preview['errors'] as $err)
                <li>{{ is_array($err) ? json_encode($err) : $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<table>
    <thead>
    <tr>
        <th>{{ __('accounting::bill.posting_preview.table.account') }}</th>
        <th>{{ __('accounting::bill.posting_preview.table.description') }}</th>
        <th style="text-align:right">{{ __('accounting::bill.posting_preview.table.debit') }}</th>
        <th style="text-align:right">{{ __('accounting::bill.posting_preview.table.credit') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach($preview['lines'] as $l)
        @php
            $accCode = TranslatableHelper::getLocalizedValue($l['account_code'] ?? null);
            $accName = TranslatableHelper::getLocalizedValue($l['account_name'] ?? null);
            $desc = TranslatableHelper::getLocalizedValue($l['description'] ?? null);
        @endphp
        <tr>
            <td>{{ trim($accCode.' '.$accName) }}</td>
            <td>{{ $desc }}</td>
            <td style="text-align:right">{{ number_format(($l['debit_minor'] ?? 0) / 100, 2) }}</td>
            <td style="text-align:right">{{ number_format(($l['credit_minor'] ?? 0) / 100, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    @php($totals = $preview['totals'])
    <tr>
        <td colspan="2">{{ __('accounting::bill.posting_preview.table.totals') }}</td>
        <td style="text-align:right">{{ number_format(($totals['debit_minor'] ?? 0) / 100, 2) }}</td>
        <td style="text-align:right">{{ number_format(($totals['credit_minor'] ?? 0) / 100, 2) }}</td>
    </tr>
    </tfoot>
</table>
</body>
</html>
