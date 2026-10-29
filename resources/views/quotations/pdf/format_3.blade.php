<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $quote->quote_number }} - Northern Emirates</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #222; margin: 0; padding: 15px; }
        .hdr { border: 1px solid #941C3C; padding: 10px; margin-bottom: 15px; }
        .hdr td { vertical-align: middle; }
        .hdr-left h2 { margin: 0; color: #941C3C; font-size: 18px; }
        .hdr-right { text-align: right; }
        .hdr-badge { background: #941C3C; color: #fff; padding: 4px 8px; font-weight: bold; font-size: 12px; }

        .info-grid { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .info-grid td { padding: 6px 8px; border: 1px solid #e0e0e0; font-size: 11px; width: 50%; }

        .items { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .items th { background: #eee; border: 1px solid #ccc; padding: 6px 8px; text-align: left; font-size: 11px; }
        .items td { border: 1px solid #ddd; padding: 6px 8px; font-size: 11px; }
        .items .num { text-align: right; }

        .totals { width: 45%; margin-left: 55%; border-collapse: collapse; margin-bottom: 15px; }
        .totals td { padding: 4px 8px; font-size: 11px; }
        .totals .final { border-top: 2px solid #941C3C; font-weight: bold; font-size: 12px; }

        .box { border: 1px solid #ddd; padding: 8px; margin-bottom: 10px; font-size: 10.5px; }
        .box h4 { margin: 0 0 4px 0; color: #941C3C; font-size: 11px; text-transform: uppercase; }
        .ftr { text-align: center; font-size: 9px; color: #888; margin-top: 20px; }
    </style>
</head>
<body>
    <table class="hdr" style="width: 100%;">
        <tr>
            <td class="hdr-left">
                <h2>RICH MOUNT CONTRACTING (SHARJAH)</h2>
                <small>{{ $branch->name }} &bull; TRN: {{ $branch->trn_number }}</small>
            </td>
            <td class="hdr-right">
                <span class="hdr-badge">{{ $quote->quote_number }}</span><br>
                <small>Date: {{ $quote->quote_date->format('d-m-Y') }}</small>
            </td>
        </tr>
    </table>

    <table class="info-grid">
        <tr>
            <td><strong>Client:</strong> {{ $lead->company_name }}<br>Attn: {{ $lead->contact_person }}</td>
            <td><strong>Project:</strong> {{ $lead->subject }}<br>Reference: {{ $lead->rfq_number ?: 'Direct' }}</td>
        </tr>
        <tr>
            <td><strong>Contact:</strong> {{ $lead->phone }} / {{ $lead->email }}</td>
            <td><strong>Validity:</strong> {{ $quote->validity_days }} Days &bull; Branch: Format 3</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 6%;">#</th>
                <th style="width: 54%;">Work Description</th>
                <th class="num" style="width: 10%;">Qty</th>
                <th style="width: 10%;">Unit</th>
                @if($outputMode !== 'tech')
                    <th class="num" style="width: 10%;">Rate</th>
                    <th class="num" style="width: 10%;">Total (AED)</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{!! \App\Support\RichText::toHtml($item->description) !!}</td>
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
    <table class="totals">
        <tr>
            <td>Amount:</td>
            <td class="num">AED {{ number_format($quote->subtotal, 2) }}</td>
        </tr>
        @if($quote->discount_amount > 0)
        <tr>
            <td>Discount:</td>
            <td class="num">- AED {{ number_format($quote->discount_amount, 2) }}</td>
        </tr>
        @endif
        @if($outputMode === 'tax')
        <tr>
            <td>VAT (5%):</td>
            <td class="num">AED {{ number_format($quote->vat_amount, 2) }}</td>
        </tr>
        <tr class="final">
            <td>Grand Total:</td>
            <td class="num">AED {{ number_format($quote->grand_total, 2) }}</td>
        </tr>
        @else
        <tr class="final">
            <td>Total (Excl. VAT):</td>
            <td class="num">AED {{ number_format($quote->taxable_amount, 2) }}</td>
        </tr>
        @endif
    </table>
    @endif

    @if($quote->technical_description)
    <div class="box">
        <h4>Scope of Work</h4>
        <p>{!! nl2br(e($quote->technical_description)) !!}</p>
    </div>
    @endif

    @if($quote->terms_conditions)
    <div class="box">
        <h4>Terms &amp; Payment Milestones</h4>
        <p>{!! nl2br(e($quote->terms_conditions)) !!}</p>
    </div>
    @endif

    <div class="ftr">
        Rich Mount Contracting LLC &bull; Sharjah &amp; Northern Emirates &bull; Format 3
    </div>
</body>
</html>
