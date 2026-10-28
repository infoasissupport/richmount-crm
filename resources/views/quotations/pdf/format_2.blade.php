<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $quote->quote_number }} - Abu Dhabi</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11.5px; color: #1a1a1a; line-height: 1.45; margin: 0; padding: 20px; }
        .top-banner { background: #080A0B; color: #fff; padding: 12px 16px; margin-bottom: 20px; border-radius: 4px; }
        .top-banner h1 { margin: 0; font-size: 18px; letter-spacing: 0.5px; }
        .top-banner span { font-size: 11px; color: #ccc; }

        .company-header { width: 100%; margin-bottom: 20px; }
        .company-header td { vertical-align: top; }
        .co-left strong { font-size: 15px; color: #941C3C; display: block; }
        .co-right { text-align: right; }

        .details-box { width: 100%; border: 1px solid #080A0B; border-collapse: collapse; margin-bottom: 20px; }
        .details-box th { background: #080A0B; color: #fff; text-align: left; padding: 6px 10px; font-size: 11px; }
        .details-box td { padding: 8px 10px; border: 1px solid #ddd; vertical-align: top; width: 50%; font-size: 11px; }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .items-table th { background: #941C3C; color: #fff; text-align: left; padding: 8px 10px; font-size: 11px; }
        .items-table td { padding: 8px 10px; border-bottom: 1px solid #e5e5e5; vertical-align: top; font-size: 11px; }
        .items-table .num { text-align: right; white-space: nowrap; }

        .totals-table { width: 45%; margin-left: 55%; border-collapse: collapse; margin-bottom: 20px; }
        .totals-table td { padding: 5px 8px; font-size: 11.5px; }
        .totals-table .grand { border-top: 2px solid #080A0B; border-bottom: 2px solid #080A0B; font-weight: bold; font-size: 13px; color: #941C3C; }

        .notes-heading { font-size: 12px; font-weight: bold; color: #080A0B; margin-top: 15px; margin-bottom: 4px; }
        .notes-body { font-size: 10.5px; color: #333; margin: 0 0 10px 0; }
        .footer { font-size: 9px; color: #777; text-align: center; margin-top: 30px; border-top: 1px solid #ccc; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="top-banner">
        <table style="width: 100%;">
            <tr>
                <td>
                    <h1>RICH MOUNT CONTRACTING &bull; ABU DHABI</h1>
                    <span>Format 2: Technical &amp; Infrastructure Engineering</span>
                </td>
                <td style="text-align: right; color: #fff;">
                    <strong>{{ $quote->quote_number }}</strong><br>
                    <span>Date: {{ $quote->quote_date->format('d/m/Y') }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="company-header">
        <tr>
            <td class="co-left" style="width: 60%;">
                <strong>{{ $branch->name }}</strong>
                <span>{{ $branch->address }}</span><br>
                <span>TRN: {{ $branch->trn_number }} &bull; Phone: {{ $branch->phone }}</span>
            </td>
            <td class="co-right" style="width: 40%;">
                <span style="display:inline-block; padding: 4px 10px; background: #941C3C; color: #fff; font-weight: bold; font-size: 11px; border-radius: 2px;">
                    @if($outputMode === 'tech') TECHNICAL SPECIFICATION @else COMMERCIAL PROPOSAL @endif
                </span>
            </td>
        </tr>
    </table>

    <table class="details-box">
        <tr>
            <th colspan="2">PROJECT &amp; CLIENT INFORMATION</th>
        </tr>
        <tr>
            <td>
                <strong>Client:</strong> {{ $lead->company_name }}<br>
                <strong>Attention:</strong> {{ $lead->contact_person ?? 'Project Director' }}<br>
                <strong>Contact:</strong> {{ $lead->phone }} &bull; {{ $lead->email }}
            </td>
            <td>
                <strong>Project:</strong> {{ $lead->subject }}<br>
                <strong>RFQ Reference:</strong> {{ $lead->rfq_number ?: 'Not Specified' }}<br>
                <strong>Engineer in Charge:</strong> {{ $quote->preparedBy->name }}
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%;">Item</th>
                <th style="width: 52%;">Specification / Scope Details</th>
                <th class="num" style="width: 10%;">Qty</th>
                <th style="width: 10%;">Unit</th>
                @if($outputMode !== 'tech')
                    <th class="num" style="width: 10%;">Rate</th>
                    <th class="num" style="width: 12%;">Amount (AED)</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($items as $idx => $item)
                <tr>
                    <td>{{ sprintf('%02d', $idx + 1) }}</td>
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
            <td>Net Total:</td>
            <td style="text-align: right;">AED {{ number_format($quote->subtotal, 2) }}</td>
        </tr>
        @if($quote->discount_amount > 0)
        <tr>
            <td>Applicable Rebate / Discount:</td>
            <td style="text-align: right;">- AED {{ number_format($quote->discount_amount, 2) }}</td>
        </tr>
        @endif
        @if($outputMode === 'tax')
        <tr>
            <td>UAE VAT ({{ (float) $quote->vat_rate * 100 }}%):</td>
            <td style="text-align: right;">AED {{ number_format($quote->vat_amount, 2) }}</td>
        </tr>
        <tr class="grand">
            <td>TOTAL PAYABLE:</td>
            <td style="text-align: right;">AED {{ number_format($quote->grand_total, 2) }}</td>
        </tr>
        @else
        <tr class="grand">
            <td>TOTAL (EXCL. TAX):</td>
            <td style="text-align: right;">AED {{ number_format($quote->taxable_amount, 2) }}</td>
        </tr>
        @endif
    </table>
    @endif

    @if($quote->technical_description)
        <div class="notes-heading">Method Statement &amp; Execution Strategy</div>
        <p class="notes-body">{!! nl2br(e($quote->technical_description)) !!}</p>
    @endif

    @if($quote->terms_conditions)
        <div class="notes-heading">Contractual Terms &amp; Conditions</div>
        <p class="notes-body">{!! nl2br(e($quote->terms_conditions)) !!}</p>
    @endif

    <div class="footer">
        Rich Mount Contracting LLC (Abu Dhabi Branch) &bull; Format 2
    </div>
</body>
</html>
