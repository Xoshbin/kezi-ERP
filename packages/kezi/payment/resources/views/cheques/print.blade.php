<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Cheque #{{ $cheque->cheque_number }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace; /* Monospace for alignment */
            margin: 0;
            padding: 0;
            width: 800px; /* Approximate width of a cheque */
            height: 350px; /* Approximate height */
            position: relative;
        }
        .cheque-container {
            position: relative;
            width: 100%;
            height: 100%;
            /* background-image: url('/images/cheque-template.png'); */ /* Optional background */
            background-size: cover;
        }
        .field {
            position: absolute;
            font-size: 14pt;
        }
        .date {
            top: 50px;
            right: 50px;
        }
        .payee {
            top: 100px;
            left: 100px;
        }
        .amount-text {
            top: 140px;
            left: 100px;
            width: 600px;
        }
        .amount-number {
            top: 140px;
            right: 50px;
            border: 1px solid #000;
            padding: 5px;
        }
        .signature {
            bottom: 50px;
            right: 50px;
        }
    </style>
</head>
<body onload="window.print()">
    <div class="cheque-container">
        <div class="field date">{{ $cheque->issue_date->format('d/m/Y') }}</div>
        <div class="field payee">{{ $cheque->partner->name }}</div>
        <div class="field amount-text">** {{ $cheque->amount_in_words ?? 'Amount in words here' }} **</div> {{-- Need to implement amount in words --}}
        <div class="field amount-number">{{ $cheque->currency->symbol }} {{ number_format($cheque->amount, 2) }}</div>
        <div class="field signature">Authorized Signature</div>
    </div>
</body>
</html>
