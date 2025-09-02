<!doctype html>
<html>
<head>
    <meta charset="utf-8"/>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; }
        th { background: #f3f4f6; text-align: left; }
        tfoot td { font-weight: bold; }
        .errors { color: #b91c1c; }
    </style>
</head>
<body>
<h2>{{ __('posting_preview.pdf.vendor_bill_heading') }}</h2>
<p>Bill: {{ $bill->bill_reference ?? $bill->id }} | Company: {{ $bill->company->name }}</p>

@if(!empty($preview['errors']))
    <div class="errors">
        <strong>Errors:</strong>
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
        <th>Account</th>
        <th>Description</th>
        <th style="text-align:right">Debit</th>
        <th style="text-align:right">Credit</th>
    </tr>
    </thead>
    <tbody>
    @foreach($preview['lines'] as $l)
        @php
            $accCode = is_array($l['account_code'] ?? null) ? (string) (reset($l['account_code']) ?: '') : (string) ($l['account_code'] ?? '');
            $accName = is_array($l['account_name'] ?? null) ? (string) (reset($l['account_name']) ?: '') : (string) ($l['account_name'] ?? '');
            $desc = $l['description'] ?? '';
            $desc = is_array($desc) ? (string) (reset($desc) ?: '') : (string) $desc;
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
        <td colspan="2">Totals</td>
        <td style="text-align:right">{{ number_format(($totals['debit_minor'] ?? 0) / 100, 2) }}</td>
        <td style="text-align:right">{{ number_format(($totals['credit_minor'] ?? 0) / 100, 2) }}</td>
    </tr>
    </tfoot>
</table>
</body>
</html>

