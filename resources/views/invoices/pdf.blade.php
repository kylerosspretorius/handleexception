<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            background: #fff;
            line-height: 1.5;
        }
        .page { padding: 48px 48px 40px; }

        /* Header */
        .header-table { width: 100%; margin-bottom: 40px; }
        .header-left { width: 60%; vertical-align: top; }
        .header-right { width: 40%; vertical-align: top; text-align: right; }

        .logo { max-width: 160px; max-height: 60px; }
        .company-name { font-size: 18px; font-weight: bold; color: #0f172a; }
        .company-detail { color: #64748b; font-size: 10px; line-height: 1.7; }

        .invoice-label { font-size: 28px; font-weight: bold; color: #0ea5e9; letter-spacing: -0.5px; }
        .invoice-number { font-size: 13px; color: #64748b; margin-top: 4px; }
        .invoice-dates { margin-top: 12px; }
        .invoice-dates td { font-size: 10px; padding: 2px 0; }
        .label-col { color: #94a3b8; width: 80px; }
        .value-col { color: #1e293b; font-weight: 600; }

        /* Divider */
        .divider { border: none; border-top: 1px solid #e2e8f0; margin: 0 0 32px; }

        /* Parties */
        .parties-table { width: 100%; margin-bottom: 36px; }
        .party-cell { width: 48%; vertical-align: top; }
        .party-spacer { width: 4%; }
        .party-label { font-size: 9px; font-weight: bold; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 8px; }
        .party-name { font-size: 13px; font-weight: bold; color: #0f172a; margin-bottom: 4px; }
        .party-detail { color: #64748b; font-size: 10px; line-height: 1.7; }

        /* Items table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .items-thead th {
            background: #f8fafc;
            padding: 10px 12px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            color: #94a3b8;
            letter-spacing: 1px;
            text-transform: uppercase;
            border-bottom: 2px solid #e2e8f0;
        }
        .items-thead th.right { text-align: right; }
        .items-tbody td {
            padding: 11px 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 11px;
            color: #334155;
        }
        .items-tbody td.right { text-align: right; font-family: 'DejaVu Sans Mono', monospace; color: #1e293b; }
        .items-tbody tr:last-child td { border-bottom: none; }

        /* Totals */
        .totals-table { width: 100%; margin-bottom: 32px; }
        .totals-spacer { width: 60%; }
        .totals-block { width: 40%; vertical-align: top; }
        .totals-inner { width: 100%; border-collapse: collapse; }
        .totals-inner td { padding: 5px 0; font-size: 11px; }
        .totals-inner .t-label { color: #64748b; }
        .totals-inner .t-value { text-align: right; font-family: 'DejaVu Sans Mono', monospace; color: #1e293b; }
        .totals-divider td { border-top: 2px solid #e2e8f0; padding-top: 10px; }
        .totals-total .t-label { font-size: 13px; font-weight: bold; color: #0f172a; }
        .totals-total .t-value { font-size: 16px; font-weight: bold; color: #0ea5e9; font-family: 'DejaVu Sans Mono', monospace; }

        /* Notes */
        .notes-box {
            background: #f8fafc;
            border-left: 3px solid #0ea5e9;
            padding: 12px 16px;
            margin-bottom: 32px;
            border-radius: 4px;
        }
        .notes-label { font-size: 9px; font-weight: bold; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 6px; }
        .notes-text { color: #475569; font-size: 10px; line-height: 1.6; }

        /* Footer */
        .footer { border-top: 1px solid #e2e8f0; padding-top: 16px; text-align: center; color: #94a3b8; font-size: 9px; }
        .footer-text { margin-bottom: 4px; }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <table class="header-table">
        <tr>
            <td class="header-left">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" class="logo" alt="Logo">
                @else
                    <div class="company-name">{{ $invoice->from_name }}</div>
                @endif
                <div class="company-detail" style="margin-top: 8px;">
                    @if($invoice->from_email){{ $invoice->from_email }}<br>@endif
                    @if($invoice->from_phone){{ $invoice->from_phone }}<br>@endif
                    @if($invoice->from_vat)VAT: {{ $invoice->from_vat }}@endif
                </div>
            </td>
            <td class="header-right">
                <div class="invoice-label">INVOICE</div>
                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                <table class="invoice-dates" style="margin-left: auto;">
                    <tr>
                        <td class="label-col">Date:</td>
                        <td class="value-col">{{ $invoice->invoice_date->format('d M Y') }}</td>
                    </tr>
                    @if($invoice->due_date)
                    <tr>
                        <td class="label-col">Due:</td>
                        <td class="value-col">{{ $invoice->due_date->format('d M Y') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="label-col">Currency:</td>
                        <td class="value-col">{{ $invoice->currency }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <hr class="divider">

    {{-- Parties --}}
    <table class="parties-table">
        <tr>
            <td class="party-cell">
                <div class="party-label">From</div>
                <div class="party-name">{{ $invoice->from_name }}</div>
                <div class="party-detail">
                    @if($invoice->from_address){!! nl2br(e($invoice->from_address)) !!}<br>@endif
                    @if($invoice->from_email){{ $invoice->from_email }}@endif
                </div>
            </td>
            <td class="party-spacer"></td>
            <td class="party-cell">
                <div class="party-label">Bill To</div>
                <div class="party-name">{{ $invoice->to_name }}</div>
                <div class="party-detail">
                    @if($invoice->to_address){!! nl2br(e($invoice->to_address)) !!}<br>@endif
                    @if($invoice->to_email){{ $invoice->to_email }}@endif
                    @if($invoice->to_vat)<br>VAT: {{ $invoice->to_vat }}@endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Line items --}}
    <table class="items-table">
        <thead class="items-thead">
            <tr>
                <th style="width:55%">Description</th>
                <th class="right" style="width:12%">Qty</th>
                <th class="right" style="width:16%">Unit Price</th>
                <th class="right" style="width:17%">Total</th>
            </tr>
        </thead>
        <tbody class="items-tbody">
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="right">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
                <td class="right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="right">{{ number_format($item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <table class="totals-table">
        <tr>
            <td class="totals-spacer"></td>
            <td class="totals-block">
                <table class="totals-inner">
                    <tr>
                        <td class="t-label">Subtotal</td>
                        <td class="t-value">{{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                    @if($invoice->tax_rate)
                    <tr>
                        <td class="t-label">Tax ({{ $invoice->tax_rate }}%)</td>
                        <td class="t-value">{{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                    @endif
                    @if($invoice->discount > 0)
                    <tr>
                        <td class="t-label">Discount</td>
                        <td class="t-value">-{{ number_format($invoice->discount, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="totals-divider totals-total">
                        <td class="t-label">TOTAL {{ $invoice->currency }}</td>
                        <td class="t-value">{{ number_format($invoice->total, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Notes --}}
    @if($invoice->notes)
    <div class="notes-box">
        <div class="notes-label">Notes</div>
        <div class="notes-text">{!! nl2br(e($invoice->notes)) !!}</div>
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        @if($invoice->footer)
            <div class="footer-text">{{ $invoice->footer }}</div>
        @endif
        <div>{{ $invoice->invoice_number }} &middot; {{ $invoice->invoice_date->format('d M Y') }} &middot; handleexception.com</div>
    </div>

</div>
</body>
</html>
