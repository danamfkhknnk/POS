<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $trx->trx_id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', ui-monospace, monospace;
            font-size: 12px;
            color: #000;
            background: #fff;
        }

        .receipt {
            width: 76mm;
            margin: 0 auto;
            padding: 4mm 2mm;
        }

        .store-name {
            text-align: center;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .store-address {
            text-align: center;
            font-size: 10px;
            margin-top: 1mm;
            white-space: pre-line;
        }

        .trx-id {
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            margin-top: 3mm;
            letter-spacing: 1px;
        }

        .meta {
            margin-top: 3mm;
            padding: 2mm 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            font-size: 11px;
        }

        .meta div {
            display: flex;
            justify-content: space-between;
        }

        table.items {
            width: 100%;
            margin-top: 2mm;
            border-collapse: collapse;
        }

        table.items td {
            padding: 1mm 0;
            vertical-align: top;
        }

        table.items td.qty {
            width: 14mm;
            text-align: center;
            white-space: nowrap;
        }

        table.items td.amount {
            text-align: right;
            white-space: nowrap;
        }

        table.items tr.sub td {
            font-size: 10px;
            color: #444;
            padding-top: 0;
        }

        tfoot td {
            border-top: 1px dashed #000;
            padding-top: 2mm;
            font-size: 14px;
            font-weight: 700;
        }

        .thanks {
            text-align: center;
            margin-top: 4mm;
            font-size: 11px;
        }

        .powered {
            text-align: center;
            font-size: 9px;
            color: #666;
            margin-top: 1mm;
        }

        @media print {
            @page {
                size: 80mm auto;
                margin: 2mm;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <p class="store-name">{{ $trx->outlet->name }}</p>
        @if ($trx->outlet->address)
            <p class="store-address">{{ $trx->outlet->address }}</p>
        @endif
        <p class="trx-id">{{ $trx->trx_id }}</p>

        <div class="meta">
            <div><span>Date</span><span>{{ $trx->sold_at->format('d M Y H:i') }}</span></div>
            <div><span>Cashier</span><span>{{ $trx->user->name }}</span></div>
        </div>

        <table class="items">
            <tbody>
                @foreach ($trx->lines as $line)
                    <tr>
                        <td>{{ $line->product->name }}</td>
                        <td class="qty">&times; {{ $line->quantity }}</td>
                        <td class="amount">{{ number_format($line->total, 0) }}</td>
                    </tr>
                    <tr class="sub">
                        <td colspan="3">@ {{ number_format($line->unit_price, 0) }} / unit</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>TOTAL</td>
                    <td class="qty"></td>
                    <td class="amount">IDR {{ number_format($trx->lines->sum('total'), 0) }}</td>
                </tr>
            </tfoot>
        </table>

        <p class="thanks">Thank you for your purchase!</p>
        <p class="powered">— {{ $trx->trx_id }} —</p>
    </div>

    <script>
        // Print only the receipt data, then close the helper window/iframe context.
        window.addEventListener('load', function () {
            window.focus();
            window.print();
        });
    </script>
</body>
</html>
