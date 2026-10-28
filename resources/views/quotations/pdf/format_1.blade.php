<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $quote->quote_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; color: #121418; line-height: 1.4; margin: 0; padding: 15px; }
        .header-table { width: 100%; border-bottom: 3px solid #941C3C; padding-bottom: 12px; margin-bottom: 15px; }
        .logo-box { width: 50%; vertical-align: top; }
        .logo-box strong { font-size: 16px; color: #121418; display: block; margin-top: 4px; }
        .logo-box small { color: #666; font-size: 10px; }
        .title-box { width: 50%; text-align: right; vertical-align: top; }
        .title-box h1 { margin: 0; font-size: 20px; color: #941C3C; text-transform: uppercase; }
        .title-box small { color: #555; font-size: 11px; }

        .meta-table { width: 100%; margin-bottom: 15px; background: #fafafa; border: 1px solid #eee; border-radius: 4px; padding: 10px; }
        .meta-table td { vertical-align: top; width: 33.33%; font-size: 11px; }
        .meta-table small { display: block; color: #888; text-transform: uppercase; font-size: 9px; margin-bottom: 3px; }
        .meta-table b { display: block; font-size: 12px; color: #000; }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .items-table th { background: #F4F5F7; text-align: left; padding: 8px; border-bottom: 2px solid #ddd; font-size: 11px; text-transform: uppercase; }
        .items-table td { padding: 8px; border-bottom: 1px solid #eee; vertical-align: top; font-size: 11px; }
        .items-table .num { text-align: right; white-space: nowrap; }

        .totals-table { width: 40%; margin-left: 60%; border-collapse: collapse; margin-bottom: 20px; }
        .totals-table td { padding: 4px 8px; font-size: 11px; }
        .totals-table .grand { border-top: 2px solid #941C3C; font-weight: bold; font-size: 13px; color: #941C3C; }

        .section-title { font-size: 11px; text-transform: uppercase; color: #941C3C; font-weight: bold; margin-top: 15px; margin-bottom: 4px; border-bottom: 1px solid #eee; padding-bottom: 2px; }
        .section-text { font-size: 10.5px; color: #444; white-space: pre-line; margin: 0 0 10px 0; }
        .footer { font-size: 9px; color: #888; text-align: center; margin-top: 25px; border-top: 1px solid #eee; padding-top: 8px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td class="logo-box">
                <strong>RICH MOUNT CONTRACTING LLC</strong>
                <small>{{ $branch->name }} &bull; TRN: {{ $branch->trn_number ?? '100 234 567 800 001' }}</small><br>
                <small>{{ $branch->address ?? 'Dubai, United Arab Emirates' }} &bull; {{ $branch->phone }}</small>
            </td>
            <td class="title-box">
                <h1>
                    @if($outputMode === 'tech')
                        TECHNICAL QUOTATION
                    @elseif($outputMode === 'notax')
                        COMMERCIAL QUOTATION
                    @else
                        TAX QUOTATION
                    @endif
                </h1>
                <small>Quote No: <b>{{ $quote->quote_number }}</b></small><br>
                <small>Date: {{ $quote->quote_date->format('d M Y') }} &bull; Validity: {{ $quote->validity_days }} days</small>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td>
                <small>Customer / Client</small>
                <b>{{ $lead->company_name }}</b>
                <span>Attn: {{ $lead->contact_person ?? 'Management' }}</span><br>
                <span>{{ $lead->phone }} &bull; {{ $lead->email }}</span>
            </td>
            <td>
                <small>Project Subject</small>
                <b>{{ $lead->subject }}</b>
                <span>RFQ / PR: {{ $lead->rfq_number ?: 'N/A' }}</span><br>
                <span>Department: {{ ucfirst($lead->department) }}</span>
            </td>
            <td>
                <small>Prepared By</small>
                <b>{{ $quote->preparedBy->name }}</b>
                <span>{{ $quote->preparedBy->role_label }}</span><br>
                <span>Branch: {{ $branch->short_name }} (Format 1)</span>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 50%;">Description</th>
                <th class="num" style="width: 10%;">Qty</th>
                <th style="width: 10%;">Unit</th>
                @if($outputMode !== 'tech')
                    <th class="num" style="width: 12%;">Unit Price</th>
                    <th class="num" style="width: 13%;">Total (AED)</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($items as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{!! nl2br(e($item->description)) !!}</td>
                    <td class="num">{{ (float) $item->quantity }}</td>
                    <td>{{ $item->unit }}</td>
                    @if($outputMode !== 'tech')
                        <td class="num">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="num">{{ number_format($item->total_amount, 2) }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($outputMode !== 'tech')
    <table class="totals-table">
        <tr>
            <td>Subtotal:</td>
            <td style="text-align: right;">AED {{ number_format($quote->subtotal, 2) }}</td>
        </tr>
        @if($quote->discount_amount > 0)
        <tr>
            <td>Discount:</td>
            <td style="text-align: right; color: red;">- AED {{ number_format($quote->discount_amount, 2) }}</td>
        </tr>
        <tr>
            <td>Taxable Amount:</td>
            <td style="text-align: right;">AED {{ number_format($quote->taxable_amount, 2) }}</td>
        </tr>
        @endif
        @if($outputMode === 'tax')
        <tr>
            <td>VAT ({{ (float) $quote->vat_rate * 100 }}%):</td>
            <td style="text-align: right;">AED {{ number_format($quote->vat_amount, 2) }}</td>
        </tr>
        <tr class="grand">
            <td>Grand Total:</td>
            <td style="text-align: right;">AED {{ number_format($quote->grand_total, 2) }}</td>
        </tr>
        @else
        <tr class="grand">
            <td>Total (Excl. VAT):</td>
            <td style="text-align: right;">AED {{ number_format($quote->taxable_amount, 2) }}</td>
        </tr>
        @endif
    </table>
    @endif

    @if($quote->technical_description)
        <div class="section-title">Scope of Work &amp; Technical Specifications</div>
        <p class="section-text">{!! nl2br(e($quote->technical_description)) !!}</p>
    @endif

    @if($quote->terms_conditions)
        <div class="section-title">Terms &amp; Conditions</div>
        <p class="section-text">{!! nl2br(e($quote->terms_conditions)) !!}</p>
    @endif

    <div class="footer">
        Generated by Rich Mount ERP &bull; Official Quotation &bull; Format 1 (Dubai Standard)
    </div>
</body>
</html>
