<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('invoice.invoice') }} {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 15mm;
            size: A4;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #2d3748;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        .container {
            width: 100%;
            max-width: 100%;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            color: rgba(239, 68, 68, 0.4);
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
            background-color: #f7fafc;
            border-radius: 8px;
            margin-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}: 10px;
        }

        .customer-details {
            background-color: #edf2f7;
            border-radius: 8px;
            margin-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}: 10px;
        }

        .section-title {
            font-weight: 600;
            font-size: 13px;
            color: #4a5568;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .company-name {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .detail-line {
            margin-bottom: 4px;
            color: #4a5568;
        }

        .invoice-meta {
            display: table;
            width: 100%;
            margin: 30px 0;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .line-items th {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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
            border-bottom: 1px solid #e2e8f0;
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
        }

        .line-items tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .line-items tr:hover {
            background-color: #edf2f7;
        }

        .amount-column {
            text-align: right !important;
            font-weight: 600;
            color: #2d3748;
        }

        .product-name {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 3px;
        }

        .product-description {
            color: #718096;
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
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .totals td {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .totals .label {
            background-color: #f7fafc;
            font-weight: 600;
            color: #4a5568;
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
        }

        .totals .amount {
            text-align: right;
            font-weight: 600;
            color: #2d3748;
        }

        .totals .total-row {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 700;
            font-size: 16px;
        }

        .footer {
            clear: both;
            margin-top: 60px;
            text-align: center;
            color: #718096;
            font-size: 10px;
            padding: 20px;
            background-color: #f7fafc;
            border-radius: 8px;
        }

        .footer .thank-you {
            font-size: 14px;
            font-weight: 600;
            color: #4a5568;
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
            <h1>{{ strtoupper(__('invoice.invoice')) }}</h1>
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
                <div class="meta-label">{{ __('invoice.invoice_date') }}</div>
                <div class="meta-value">{{ $invoice->invoice_date->format('M d, Y') }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">{{ __('invoice.due_date') }}</div>
                <div class="meta-value">{{ $invoice->due_date->format('M d, Y') }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">{{ __('invoice.currency') }}</div>
                <div class="meta-value">{{ $currency->code }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">{{ __('invoice.status') }}</div>
                <div class="meta-value">{{ $invoice->status->label() }}</div>
            </div>
        </div>

        <div class="line-items">
            <table>
                <thead>
                    <tr>
                        <th style="width: 40%;">{{ __('invoice.description') }}</th>
                        <th style="width: 15%;">Qty</th>
                        <th style="width: 20%;">{{ __('invoice.unit_price') }}</th>
                        <th style="width: 25%;">Total</th>
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
                        <td class="amount-column">{{ number_format($line->quantity, 2) }}</td>
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
            <div class="thank-you">Thank you for your business!</div>
            @if($company->website)
                <div>Visit us at: {{ $company->website }}</div>
            @endif
            <div>Generated on {{ now()->format('Y-m-d H:i:s') }}</div>
        </div>
    </div>
</body>
</html>
