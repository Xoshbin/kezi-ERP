<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('sales::invoice.invoice') }} {{ $invoice->invoice_number }}</title>
    @php
        use Brick\Money\Money;use Filament\Support\Facades\FilamentColor;
        use Filament\Support\Colors\Color as FsColor;
        $gray = FilamentColor::getColor('gray');
        $g50 = FsColor::convertToRgb($gray[50]);
        $g100 = FsColor::convertToRgb($gray[100]);
        $g900 = FsColor::convertToRgb($gray[900]);
        if (preg_match('/rgb\((\d+),\s*(\d+),\s*(\d+)\)/', $g900, $m)) {
            $g900a20 = "rgba({$m[1]}, {$m[2]}, {$m[3]}, 0.2)";
        } else {
            $g900a20 = 'rgba(0, 0, 0, 0.2)';
        }
    @endphp
    <style>
        @page {
            margin: 20mm;
            size: A4;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.6;
            color: {{ $g900 }};
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid{{ $g900 }};
            position: relative;
        }

        .header h1 {
            font-size: 24px;
            margin: 0;
            font-weight: bold;
            letter-spacing: 3px;
        }

        .invoice-number {
            font-size: 12px;
            margin-top: 10px;
            font-weight: normal;
        }

        .draft-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 48px;
            color: {{ $g900a20 }};
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
            padding: 0 15px;
        }

        .section-title {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .company-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .detail-line {
            margin-bottom: 3px;
            font-size: 10px;
        }

        .invoice-meta {
            margin: 30px 0;
            text-align: center;
        }

        .invoice-meta table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 auto;
        }

        .invoice-meta td {
            padding: 8px 15px;
            border: 1px solid{{ $g900 }};
            font-size: 10px;
        }

        .invoice-meta .label {
            font-weight: bold;
            background-color: {{ $g100 }};
        }

        .line-items {
            margin: 30px 0;
        }

        .line-items table {
            width: 100%;
            border-collapse: collapse;
        }

        .line-items th {
            background-color: {{ $g900 }};
            color: white;
            padding: 10px 8px;
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .line-items td {
            padding: 8px;
            border: 1px solid{{ $g900 }};
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
            font-size: 10px;
        }

        .amount-column {
            text-align: right !important;
            font-weight: bold;
        }

        .product-name {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .totals {
            margin-top: 30px;
            float: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }};
            width: 250px;
        }

        .totals table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals td {
            padding: 8px 10px;
            border: 1px solid{{ $g900 }};
            font-size: 10px;
        }

        .totals .label {
            background-color: {{ $g100 }};
            font-weight: bold;
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
        }

        .totals .amount {
            text-align: right;
            font-weight: bold;
        }

        .totals .total-row {
            background-color: {{ $g900 }};
            color: white;
            font-weight: bold;
            font-size: 12px;
        }

        .footer {
            clear: both;
            margin-top: 50px;
            text-align: center;
            font-size: 9px;
            border-top: 1px solid{{ $g900 }};
            padding-top: 20px;
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
        <div class="invoice-number">Number: {{ $invoice->invoice_number ?? 'DRAFT' }}</div>
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
                <div class="detail-line">{{ __('company.tax_id') }}: {{ $company->tax_id }}</div>
            @endif
            @if($company->phone)
                <div class="detail-line">{{ __('partner.phone') }}: {{ $company->phone }}</div>
            @endif
            @if($company->email)
                <div class="detail-line">{{ __('partner.email') }}: {{ $company->email }}</div>
            @endif
        </div>

        <div class="customer-details">
            <div class="section-title">To</div>
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
                <div class="detail-line">{{ __('partner.tax_id') }}: {{ $customer->tax_id }}</div>
            @endif
            @if($customer->phone)
                <div class="detail-line">{{ __('partner.phone') }}: {{ $customer->phone }}</div>
            @endif
            @if($customer->email)
                <div class="detail-line">{{ __('partner.email') }}: {{ $customer->email }}</div>
            @endif
        </div>
    </div>

    <div class="invoice-meta">
        <table>
            <tr>
                <td class="label">{{ __('sales::invoice.invoice_date') }}</td>
                <td>{{ $invoice->invoice_date->format('Y-m-d') }}</td>
                <td class="label">{{ __('sales::invoice.due_date') }}</td>
                <td>{{ $invoice->due_date->format('Y-m-d') }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('sales::invoice.currency') }}</td>
                <td>{{ $currency->code }}</td>
                <td class="label">{{ __('sales::invoice.status') }}</td>
                <td>{{ $invoice->status->label() }}</td>
            </tr>
        </table>
    </div>

    <div class="line-items">
        <table>
            <thead>
            <tr>
                <th style="width: 45%;">{{ __('sales::invoice.description') }}</th>
                <th style="width: 15%;">Qty</th>
                <th style="width: 20%;">{{ __('sales::invoice.price') }}</th>
                <th style="width: 20%;">{{ __('sales::invoice.total') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($invoice->invoiceLines as $line)
                <tr>
                    <td>
                        @if($line->product)
                            <div class="product-name">{{ $line->product->name }}</div>
                        @endif
                        {{ $line->description }}
                    </td>
                    <td class="amount-column">{{ \Jmeryar\Foundation\Support\NumberFormatter::formatNumber($line->quantity, 2) }}</td>
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
        <p>Thank you for your business</p>
        @if($company->website)
            <p>{{ $company->website }}</p>
        @endif
    </div>
</div>
</body>
</html>
