<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('sales::invoice.invoice') }} {{ $invoice->invoice_number }}</title>
    @php
        use Brick\Money\Money;use Filament\Support\Facades\FilamentColor;
        use Filament\Support\Colors\Color as FsColor;
        $primary = FilamentColor::getColor('primary');
        $info = FilamentColor::getColor('info');
        $gray = FilamentColor::getColor('gray');
        $danger = FilamentColor::getColor('danger');
        // Convert to rgb() for PDF compatibility
        $p500 = FsColor::convertToRgb($primary[500]);
        $p600 = FsColor::convertToRgb($primary[600]);
        $p700 = FsColor::convertToRgb($primary[700]);
        $i500 = FsColor::convertToRgb($info[500]);
        $i600 = FsColor::convertToRgb($info[600]);
        $g50 = FsColor::convertToRgb($gray[50]);
        $g100 = FsColor::convertToRgb($gray[100]);
        $g200 = FsColor::convertToRgb($gray[200]);
        $g600 = FsColor::convertToRgb($gray[600]);
        $g700 = FsColor::convertToRgb($gray[700]);
        $g800 = FsColor::convertToRgb($gray[800]);
        $d500 = FsColor::convertToRgb($danger[500]);
        if (preg_match('/rgb\((\d+),\s*(\d+),\s*(\d+)\)/', $d500, $m)) {
            $d500a40 = "rgba({$m[1]}, {$m[2]}, {$m[3]}, 0.4)";
        } else {
            $d500a40 = 'rgba(239, 68, 68, 0.4)';
        }
        if (preg_match('/rgb\((\d+),\s*(\d+),\s*(\d+)\)/', $g800, $m2)) {
            $g800a10 = "rgba({$m2[1]}, {$m2[2]}, {$m2[3]}, 0.1)";
        } else {
            $g800a10 = 'rgba(0, 0, 0, 0.1)';
        }
    @endphp
    <style>
        @page {
            margin: 15mm;
            size: A4;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: {{ $g700 }};
            margin: 0;
            padding: 0;
            background-color: {{ $g50 }};
        }

        .container {
            width: 100%;
            max-width: 100%;
        }

        .header {
            background: linear-gradient(135deg, {{ $p600 }} 0%, {{ $p700 }} 100%);
            color: white;
            padding: 25px;
            margin-bottom: 30px;
            border-radius: 8px;
            position: relative;
        }

        .header h1 {
            font-size: 32px;
            margin: 0;
            font-weight: 300;
            letter-spacing: 2px;
        }

        .header .invoice-number {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
        }

        .draft-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 48px;
            color: {{ $d500a40 }};
            font-weight: bold;
            z-index: 10;
            pointer-events: none;
        }

        .invoice-details {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .company-details, .customer-details {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 20px;
        }

        .company-details {
            background-color: {{ $g50 }};
            border-radius: 8px;
            margin- {{ app()->getLocale() === 'ar' ? 'left' : 'right' }}: 10px;
        }

        .customer-details {
            background-color: {{ $g100 }};
            border-radius: 8px;
            margin- {{ app()->getLocale() === 'ar' ? 'right' : 'left' }}: 10px;
        }

        .section-title {
            font-weight: 600;
            font-size: 13px;
            color: {{ $g700 }};
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .company-name {
            font-size: 16px;
            font-weight: 600;
            color: {{ $g800 }};
            margin-bottom: 8px;
        }

        .detail-line {
            margin-bottom: 4px;
            color: {{ $g700 }};
        }

        .invoice-meta {
            display: table;
            width: 100%;
            margin: 30px 0;
            background: linear-gradient(135deg, {{ $p500 }} 0%, {{ $p600 }} 100%);
            color: white;
            border-radius: 8px;
            padding: 20px;
        }

        .meta-item {
            display: table-cell;
            width: 25%;
            text-align: center;
            vertical-align: middle;
        }

        .meta-label {
            font-size: 10px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .meta-value {
            font-size: 14px;
            font-weight: 600;
        }

        .line-items {
            margin: 30px 0;
        }

        .line-items table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px{{ $g800a10 }};
        }

        .line-items th {
            background: linear-gradient(135deg, {{ $i500 }} 0%, {{ $i600 }} 100%);
            color: white;
            padding: 15px 12px;
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .line-items td {
            padding: 12px;
            border-bottom: 1px solid{{ $g200 }};
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
        }

        .line-items tr:nth-child(even) {
            background-color: {{ $g50 }};
        }

        .line-items tr:hover {
            background-color: {{ $g100 }};
        }

        .amount-column {
            text-align: right !important;
            font-weight: 600;
            color: {{ $g800 }};
        }

        .product-name {
            font-weight: 600;
            color: {{ $g800 }};
            margin-bottom: 3px;
        }

        .product-description {
            color: {{ $g600 }};
            font-size: 10px;
        }

        .totals {
            margin-top: 40px;
            float: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }};
            width: 350px;
        }

        .totals table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px{{ $g800a10 }};
        }

        .totals td {
            padding: 12px 15px;
            border-bottom: 1px solid{{ $g200 }};
        }

        .totals .label {
            background-color: {{ $g50 }};
            font-weight: 600;
            color: {{ $g700 }};
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
        }

        .totals .amount {
            text-align: right;
            font-weight: 600;
            color: {{ $g800 }};
        }

        .totals .total-row {
            background: linear-gradient(135deg, {{ $p600 }} 0%, {{ $p700 }} 100%);
            color: white;
            font-weight: 700;
            font-size: 16px;
        }

        .footer {
            clear: both;
            margin-top: 60px;
            text-align: center;
            color: {{ $g600 }};
            font-size: 10px;
            padding: 20px;
            background-color: {{ $g50 }};
            border-radius: 8px;
        }

        .footer .thank-you {
            font-size: 14px;
            font-weight: 600;
            color: {{ $g700 }};
            margin-bottom: 10px;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>{{ strtoupper(__('sales::invoice.invoice')) }}</h1>
        <div class="invoice-number"># {{ $invoice->invoice_number ?? 'DRAFT' }}</div>
        @if($invoice->status === 'draft')
            <div class="draft-watermark">DRAFT</div>
        @endif
    </div>

    <div class="invoice-details clearfix">
        <div class="company-details">
            <div class="section-title">From</div>
            <div class="company-name">{{ $company->name }}</div>
            @if($company->address)
                <div class="detail-line">{{ $company->address }}</div>
            @endif
            @if($company->city)
                <div class="detail-line">{{ $company->city }}</div>
            @endif
            @if($company->tax_id)
                <div class="detail-line"><strong>{{ __('company.tax_id') }}:</strong> {{ $company->tax_id }}</div>
            @endif
            @if($company->phone)
                <div class="detail-line"><strong>{{ __('partner.phone') }}:</strong> {{ $company->phone }}</div>
            @endif
            @if($company->email)
                <div class="detail-line"><strong>{{ __('partner.email') }}:</strong> {{ $company->email }}</div>
            @endif
        </div>

        <div class="customer-details">
            <div class="section-title">Bill To</div>
            <div class="company-name">{{ $customer->name }}</div>
            @if($customer->address_line_1)
                <div class="detail-line">{{ $customer->address_line_1 }}</div>
            @endif
            @if($customer->address_line_2)
                <div class="detail-line">{{ $customer->address_line_2 }}</div>
            @endif
            @if($customer->city)
                <div class="detail-line">{{ $customer->city }}, {{ $customer->state }} {{ $customer->zip_code }}</div>
            @endif
            @if($customer->tax_id)
                <div class="detail-line"><strong>{{ __('partner.tax_id') }}:</strong> {{ $customer->tax_id }}</div>
            @endif
            @if($customer->phone)
                <div class="detail-line"><strong>{{ __('partner.phone') }}:</strong> {{ $customer->phone }}</div>
            @endif
            @if($customer->email)
                <div class="detail-line"><strong>{{ __('partner.email') }}:</strong> {{ $customer->email }}</div>
            @endif
        </div>
    </div>

    <div class="invoice-meta">
        <div class="meta-item">
            <div class="meta-label">{{ __('sales::invoice.invoice_date') }}</div>
            <div class="meta-value">{{ $invoice->invoice_date->format('M d, Y') }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">{{ __('sales::invoice.due_date') }}</div>
            <div class="meta-value">{{ $invoice->due_date->format('M d, Y') }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">{{ __('sales::invoice.currency') }}</div>
            <div class="meta-value">{{ $currency->code }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">{{ __('sales::invoice.status') }}</div>
            <div class="meta-value">{{ $invoice->status->label() }}</div>
        </div>
    </div>

    <div class="line-items">
        <table>
            <thead>
            <tr>
                <th style="width: 40%;">{{ __('sales::invoice.description') }}</th>
                <th style="width: 15%;">Qty</th>
                <th style="width: 20%;">{{ __('sales::invoice.unit_price') }}</th>
                <th style="width: 25%;">{{ __('sales::invoice.total') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($invoice->invoiceLines as $line)
                <tr>
                    <td>
                        @if($line->product)
                            <div class="product-name">{{ $line->product->name }}</div>
                        @endif
                        <div class="product-description">{{ $line->description }}</div>
                    </td>
                    <td class="amount-column">{{ \Kezi\Foundation\Support\NumberFormatter::formatNumber($line->quantity, 2) }}</td>
                    <td class="amount-column">{{ $line->unit_price }}</td>
                    <td class="amount-column">{{ $line->subtotal }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="totals">
        <table>
            <tr>
                <td class="label">Subtotal</td>
                <td class="amount">{{ $invoice->invoiceLines->reduce(fn($carry, $line) => $carry->plus($line->subtotal), Money::of(0, $currency->code)) }}</td>
            </tr>
            @if($invoice->total_tax->isPositive())
                <tr>
                    <td class="label">{{ __('sales::invoice.tax') }}</td>
                    <td class="amount">{{ $invoice->total_tax }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td class="label">{{ __('sales::invoice.total_amount') }}</td>
                <td class="amount">{{ $invoice->total_amount }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <div class="thank-you">Thank you for your business!</div>
        @if($company->website)
            <div>Visit us at: {{ $company->website }}</div>
        @endif
        <div>Generated on {{ now()->format('Y-m-d H:i:s') }}</div>
    </div>
</div>
</body>
</html>
