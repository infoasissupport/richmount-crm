<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }} - Tax Invoice</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11.5px; color: #121418; line-height: 1.4; margin: 0; padding: 15px; }
        .header-table { width: 100%; border-bottom: 3px solid #941C3C; padding-bottom: 12px; margin-bottom: 15px; }
        .logo-box { width: 55%; vertical-align: top; }
        .logo-box strong { font-size: 16px; color: #121418; display: block; margin-top: 4px; }
        .logo-box small { color: #666; font-size: 10px; }
        .title-box { width: 45%; text-align: right; vertical-align: top; }
        .title-box h1 { margin: 0; font-size: 18px; color: #941C3C; text-transform: uppercase; }
        .title-box h2 { margin: 0; font-size: 13px; color: #555; }
        .title-box small { color: #555; font-size: 11px; }

        .meta-table { width: 100%; margin-bottom: 15px; background: #fafafa; border: 1px solid #eee; border-radius: 4px; padding: 10px; }
        .meta-table td { vertical-align: top; width: 33.33%; font-size: 11px; }
        .meta-table small { display: block; color: #888; text-transform: uppercase; font-size: 9px; margin-bottom: 3px; }
        .meta-table b { display: block; font-size: 12px; color: #000; }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .items-table th { background: #F4F5F7; text-align: left; padding: 8px; border-bottom: 2px solid #ddd; font-size: 10.5px; text-transform: uppercase; }
        .items-table td { padding: 8px; border-bottom: 1px solid #eee; vertical-align: top; font-size: 11px; }
        .items-table .num { text-align: right; white-space: nowrap; }

        .totals-table { width: 45%; margin-left: 55%; border-collapse: collapse; margin-bottom: 20px; }
        .totals-table td { padding: 4px 8px; font-size: 11px; }
        .totals-table .grand { border-top: 2px solid #941C3C; font-weight: bold; font-size: 13px; color: #941C3C; }

        .bank-box { background: #fdfdfd; border: 1px solid #ddd; border-left: 3px solid #941C3C; padding: 10px; margin-bottom: 15px; font-size: 10.5px; }
        .bank-box b { color: #941C3C; text-transform: uppercase; font-size: 10px; display: block; margin-bottom: 4px; }

        .footer { font-size: 9px; color: #888; text-align: center; margin-top: 25px; border-top: 1px solid #eee; padding-top: 8px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td class="logo-box">
                <strong>RICH MOUNT CONTRACTING LLC</strong>
                <small>{{ $branch->name }}</small><br>
                <small>TRN (Tax Registration Number): <b>{{ $branch->trn_number ?? '100 234 567 800 001' }}</b></small><br>
                <small>{{ $branch->address ?? 'Dubai, United Arab Emirates' }} &bull; Tel: {{ $branch->phone }}</small>
            </td>
            <td class="title-box">
                <h1>TAX INVOICE</h1>
                <h2>فاتورة ضريبية</h2>
                <small>Invoice No: <b>{{ $invoice->invoice_number }}</b></small><br>
                <small>Invoice Date: {{ $invoice->invoice_date->format('d M Y') }} &bull; Due: {{ $invoice->due_date->format('d M Y') }}</small>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td>
                <small>Bill To / Customer (العميل)</small>
                <b>{{ $invoice->client_name }}</b>
                @if($invoice->client_trn)
                    <span>Customer TRN: {{ $invoice->client_trn }}</span><br>
                @endif
                @if($invoice->client_address)
                    <span>{{ $invoice->client_address }}</span>
                @else
                    <span>United Arab Emirates</span>
                @endif
            </td>
            <td>
                <small>Project Reference</small>
                <b>{{ $project->project_number }}</b>
                <span>{{ $project->name }}</span><br>
                @if($invoice->client_po_number)
                    <small style="margin-top:4px;">Client PO: {{ $invoice->client_po_number }}</small>
                @endif
            </td>
            <td>
                <small>Payment Status & Type</small>
                <b>{{ $invoice->is_final ? 'Final Tax Invoice' : 'Progress Milestone' }}</b>
                <span>Status: <b style="color:#941C3C;">{{ strtoupper($invoice->status) }}</b></span><br>
                <span>Balance Due: <b>AED {{ number_format($invoice->balance_due, 2) }}</b></span>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 50%;">Description (الوصف)</th>
                <th class="num" style="width: 8%;">Qty</th>
                <th style="width: 8%;">Unit</th>
                <th class="num" style="width: 14%;">Rate (AED)</th>
                <th class="num" style="width: 15%;">Taxable (AED)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $idx => $it)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $it->description }}</td>
                    <td class="num">{{ number_format($it->quantity, 2) }}</td>
                    <td>{{ $it->unit }}</td>
                    <td class="num">{{ number_format($it->unit_price, 2) }}</td>
                    <td class="num">{{ number_format($it->total_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Gross Subtotal (الإجمالي):</td>
            <td class="num">AED {{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        @if($invoice->discount_amount > 0)
            <tr>
                <td>Discount (الخصم):</td>
                <td class="num">- AED {{ number_format($invoice->discount_amount, 2) }}</td>
            </tr>
        @endif
        <tr>
            <td>Net Taxable Amount (الخاضع للضريبة):</td>
            <td class="num">AED {{ number_format($invoice->taxable_amount, 2) }}</td>
        </tr>
        <tr>
            <td>UAE VAT 5% (ضريبة القيمة المضافة):</td>
            <td class="num">AED {{ number_format($invoice->vat_amount, 2) }}</td>
        </tr>
        <tr class="grand">
            <td>Invoice Total (المجموع الكلي):</td>
            <td class="num">AED {{ number_format($invoice->grand_total, 2) }}</td>
        </tr>
        @if($invoice->paid_amount > 0)
            <tr>
                <td>Amount Paid (المدفوع):</td>
                <td class="num" style="color:green;">- AED {{ number_format($invoice->paid_amount, 2) }}</td>
            </tr>
            <tr style="font-weight:bold;">
                <td>Remaining Balance (المتبقي):</td>
                <td class="num">AED {{ number_format($invoice->balance_due, 2) }}</td>
            </tr>
        @endif
    </table>

    <div class="bank-box">
        <b>Bank Remittance Details (بيانات الحساب البنكي):</b>
        Bank Name: Emirates NBD Bank PJSC &bull; Branch: Al Barsha, Dubai<br>
        Account Name: Rich Mount Contracting LLC<br>
        Account Number: 102 345 678 901 &bull; IBAN: AE07 0260 0010 2345 6789 01<br>
        Swift Code: ENBD AE AD
    </div>

    @if($invoice->payment_terms)
        <div style="font-size:10px; color:#555; margin-bottom: 10px;">
            <b>Payment Terms:</b> {{ $invoice->payment_terms }}
        </div>
    @endif

    <div class="footer">
        Official UAE VAT Compliant Tax Invoice &bull; Generated by Rich Mount ERP on {{ now()->format('d M Y, h:i A') }} &bull; Page 1 of 1
    </div>
</body>
</html>
