<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('invoice.invoice') }} {{ $invoice->invoice_number }}</title>
    @php
        use Filament\Support\Colors\Color as FsColor;
        $primary = \Filament\Support\Facades\FilamentColor::getColor('primary');
        $gray = \Filament\Support\Facades\FilamentColor::getColor('gray');
        $danger = \Filament\Support\Facades\FilamentColor::getColor('danger');
        // Convert to rgb() for PDF compatibility
        $p600 = FsColor::convertToRgb($primary[600]);
        $g800 = FsColor::convertToRgb($gray[800] ?? $gray[700]);
        $g700 = FsColor::convertToRgb($gray[700]);
        $g500 = FsColor::convertToRgb($gray[500]);
        $g200 = FsColor::convertToRgb($gray[200]);
        $g100 = FsColor::convertToRgb($gray[100]);
        $g50 = FsColor::convertToRgb($gray[50] ?? $gray[100]);
    @endphp
    <style>
        @page {
            margin: 20mm;
            size: A4;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: {{ $g700 }};
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid {{ $p600 }};
            padding-bottom: 20px;
            position: relative;
        }

        .header h1 {
            color: {{ $p600 }};
            font-size: 28px;
            margin: 0;
            font-weight: bold;
        }

        .draft-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 48px;
            color: rgba(239, 68, 68, 0.3);
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
            padding: 0 10px;
        }

        .company-details {
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
        }

        .customer-details {
            text-align: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }};
        }

        .section-title {
            font-weight: bold;
            font-size: 14px;
            color: {{ $p600 }};
            margin-bottom: 10px;
            border-bottom: 1px solid {{ $g200 }};
            padding-bottom: 5px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: {{ $g800 }};
            margin-bottom: 5px;
        }

        .invoice-meta {
            margin: 30px 0;
            background-color: {{ $g50 }};
            padding: 15px;
            border-radius: 5px;
        }

        .invoice-meta table {
            width: 100%;
            border-collapse: collapse;
        }

        .invoice-meta td {
            padding: 5px 10px;
            border: none;
        }

        .invoice-meta .label {
            font-weight: bold;
            color: {{ $g700 }};
            width: 40%;
        }

        .line-items {
            margin: 30px 0;
        }

        .line-items table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .line-items th {
            background-color: {{ $p600 }};
            color: white;
            padding: 12px 8px;
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
            font-weight: bold;
            border: 1px solid {{ $p600 }};
        }

        .line-items td {
            padding: 10px 8px;
            border: 1px solid {{ $g200 }};
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
        }

        .line-items tr:nth-child(even) {
            background-color: {{ $g50 }};
        }

        .amount-column {
            text-align: right !important;
            font-weight: bold;
        }

        .totals {
            margin-top: 30px;
            float: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }};
            width: 300px;
        }

        .totals table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals td {
            padding: 8px 12px;
            border: 1px solid {{ $g200 }};
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
            background-color: {{ $p600 }};
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        .footer {
            clear: both;
            margin-top: 50px;
            text-align: center;
            color: {{ $g500 }};
            font-size: 11px;
            border-top: 1px solid {{ $g200 }};
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
            <h1>{{ strtoupper(__('invoice.invoice')) }}</h1>
            @if($invoice->status === 'draft')
                <div class="draft-watermark">DRAFT</div>
            @endif
        </div>

        <div class="invoice-details clearfix">
            <div class="company-details">
                <div class="section-title">From</div>
                <div class="company-name">{{ $company->name }}</div>
                @if($company->address)
                    <div>{{ $company->address }}</div>
                @endif
                @if($company->city)
                    <div>{{ $company->city }}</div>
                @endif
                @if($company->tax_id)
                    <div><strong>{{ __('company.tax_id') }}:</strong> {{ $company->tax_id }}</div>
                @endif
                @if($company->phone)
                    <div><strong>{{ __('partner.phone') }}:</strong> {{ $company->phone }}</div>
                @endif
                @if($company->email)
                    <div><strong>{{ __('partner.email') }}:</strong> {{ $company->email }}</div>
                @endif
            </div>

            <div class="customer-details">
                <div class="section-title">Bill To</div>
                <div class="company-name">{{ $customer->name }}</div>
                @if($customer->address_line_1)
                    <div>{{ $customer->address_line_1 }}</div>
                @endif
                @if($customer->address_line_2)
                    <div>{{ $customer->address_line_2 }}</div>
                @endif
                @if($customer->city)
                    <div>{{ $customer->city }}, {{ $customer->state }} {{ $customer->zip_code }}</div>
                @endif
                @if($customer->tax_id)
                    <div><strong>{{ __('partner.tax_id') }}:</strong> {{ $customer->tax_id }}</div>
                @endif
                @if($customer->phone)
                    <div><strong>{{ __('partner.phone') }}:</strong> {{ $customer->phone }}</div>
                @endif
                @if($customer->email)
                    <div><strong>{{ __('partner.email') }}:</strong> {{ $customer->email }}</div>
                @endif
            </div>
        </div>

        <div class="invoice-meta">
            <table>
                <tr>
                    <td class="label">{{ __('invoice.invoice_number') }}:</td>
                    <td><strong>{{ $invoice->invoice_number }}</strong></td>
                    <td class="label">{{ __('invoice.invoice_date') }}:</td>
                    <td><strong>{{ $invoice->invoice_date->format('Y-m-d') }}</strong></td>
                </tr>
                <tr>
                    <td class="label">{{ __('invoice.due_date') }}:</td>
                    <td><strong>{{ $invoice->due_date->format('Y-m-d') }}</strong></td>
                    <td class="label">{{ __('invoice.currency') }}:</td>
                    <td><strong>{{ $currency->code }}</strong></td>
                </tr>
            </table>
        </div>

        <div class="line-items">
            <div class="section-title">Items</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 40%;">{{ __('invoice.description') }}</th>
                        <th style="width: 15%;">{{ __('invoice.quantity') }}</th>
                        <th style="width: 20%;">{{ __('invoice.unit_price') }}</th>
                        <th style="width: 25%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->invoiceLines as $line)
                    <tr>
                        <td>
                            @if($line->product)
                                <strong>{{ $line->product->name }}</strong><br>
                            @endif
                            {{ $line->description }}
                        </td>
                        <td class="amount-column">{{ \App\Support\NumberFormatter::formatNumber($line->quantity, 2) }}</td>
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
                    <td class="amount">{{ $invoice->invoiceLines->reduce(fn($carry, $line) => $carry->plus($line->subtotal), \Brick\Money\Money::of(0, $currency->code)) }}</td>
                </tr>
                @if($invoice->total_tax->isPositive())
                <tr>
                    <td class="label">{{ __('invoice.tax') }}</td>
                    <td class="amount">{{ $invoice->total_tax }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td class="label">{{ __('invoice.total_amount') }}</td>
                    <td class="amount">{{ $invoice->total_amount }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>Thank you for your business!</p>
            @if($company->website)
                <p>{{ $company->website }}</p>
            @endif
        </div>
    </div>
</body>
</html>
