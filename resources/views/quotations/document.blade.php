{{--
    Standalone quotation document (same design as the builder's on-screen preview), built from the saved quotation.
    Shown inside the lead page's preview popup; $mode is tax | notax | tech.
--}}
@php
    $priced = $mode !== 'tech';
    $lead = $quote->lead;
    $engineer = $lead?->assignedEngineer;
    $engineerName = strtoupper($engineer?->name ?? '—');
    $vatPct = round((float) $quote->vat_rate * 100);
    $discount = (float) $quote->discount_amount;
    $total = $mode === 'tax' ? (float) $quote->grand_total : (float) $quote->taxable_amount;
    $money = fn ($v) => number_format((float) $v, 2);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $quote->quote_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    @include('quotations.partials.document-css')
    <style>
        html, body { margin: 0; background: #F7F8FA; }
        body { padding: 16px; }
        .qdoc { box-shadow: 0 1px 3px rgba(16, 24, 40, .08); border: 1px solid #E2E8F0; border-radius: 4px; }
        @media print {
            html, body { background: #fff; padding: 0; }
            .qdoc { box-shadow: none; border: 0; max-width: none; }
        }
    </style>
</head>
<body>
<div class="qdoc">
    <div class="qd-head">
        <img src="{{ asset('images/rich-mount-logo.png') }}" alt="Rich Mount Contracting &amp; Interiors">
        <div class="qd-co">
            <div class="ar">ريتش ماونت للمقاولات والداخلية ذ م م</div>
            <div class="en">Rich Mount Contracting &amp; Interiors L.L.C</div>
            <small>Tel: 042950285 | PO Box 97921 | Dubai, UAE</small>
            <small>www.rich-mounts.com | info@rich-mounts.com</small>
        </div>
    </div>

    <div class="qd-title">{{ $mode === 'tech' ? 'TECHNICAL QUOTATION' : 'QUOTATION' }}</div>

    <table class="qd-info">
        <tr><th colspan="2">CLIENT DETAILS:</th><th colspan="2">QUOTE DETAILS:</th></tr>
        <tr><td class="k">Company Name:</td><td>{{ $lead?->company_name ?? '—' }}</td><td class="k">Date:</td><td>{{ $quote->quote_date?->format('d M Y') }}</td></tr>
        <tr><td class="k">Address:</td><td>—</td><td class="k">Quote Number:</td><td>{{ $quote->quote_number }}</td></tr>
        <tr><td class="k">PR Number:</td><td>{{ $lead?->rfq_number ?: 'N/A' }}</td><td class="k">Site Visited By:</td><td>{{ $engineerName }}</td></tr>
    </table>

    <div class="qd-proj">PROJECT: {{ $lead?->subject }}</div>

    <table class="qd-items">
        <thead>
            <tr>
                <th>Sl. No.</th>
                <th>Description</th>
                <th>Qty</th>
                <th>Unit</th>
                @if($priced)
                    <th>Rate</th>
                    <th>Price</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @php $itemNo = 0; @endphp
            @forelse($quote->lineItems as $item)
                @if($item->isSection())
                    {{-- section heading (e.g. PRELIMINARIES) across the table; items keep counting 1, 2, 3… --}}
                    <tr class="sect"><td colspan="{{ $priced ? 6 : 4 }}">{{ $item->description }}</td></tr>
                    @continue
                @endif
                @if($item->isSubDesc())
                    @continue
                @endif
                @php $itemNo++; @endphp
                @php
                    $subdescs = $quote->lineItems->slice($loop->index + 1)->takeWhile(fn ($next) => $next->isSubDesc());
                    $groupRowspan = 1 + $subdescs->count();
                @endphp
                <tr>
                    <td class="c" rowspan="{{ $groupRowspan }}" style="vertical-align: middle;">{{ $itemNo }}</td>
                    <td class="d">
                        {!! \App\Support\RichText::toHtml($item->description) ?: e('Item ' . $itemNo) !!}
                        @if(!empty($item->photos))
                            <div class="qd-photos">
                                @foreach($item->photos as $n => $photo)
                                    <img src="{{ asset('storage/' . $photo) }}" alt="Item {{ $itemNo }} image {{ $n + 1 }}">
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td class="c" rowspan="{{ $groupRowspan }}" style="vertical-align: middle;">{{ (float) $item->quantity }}</td>
                    <td class="c" rowspan="{{ $groupRowspan }}" style="vertical-align: middle;">{{ $item->unit }}</td>
                    @if($priced)
                        <td class="c" rowspan="{{ $groupRowspan }}" style="vertical-align: middle;">{{ $money($item->unit_price) }}</td>
                        <td class="r" rowspan="{{ $groupRowspan }}" style="vertical-align: middle;">{{ $money($item->total_amount) }}</td>
                    @endif
                </tr>
                @foreach($subdescs as $subdesc)
                    <tr><td class="d">{!! \App\Support\RichText::toHtml($subdesc->description) !!}</td></tr>
                @endforeach
            @empty
                <tr><td colspan="{{ $priced ? 6 : 4 }}" class="c" style="padding: 16px;">No line items added yet.</td></tr>
            @endforelse

            @if($priced)
                @if($discount > 0 || $mode === 'tax')
                    <tr class="sum"><td colspan="4" style="text-align:right">SUB TOTAL</td><td class="c">AED</td><td class="r">{{ $money($quote->subtotal) }}</td></tr>
                @endif
                @if($discount > 0)
                    <tr class="sum"><td colspan="4" style="text-align:right">DISCOUNT</td><td class="c">AED</td><td class="r">− {{ $money($discount) }}</td></tr>
                @endif
                @if($mode === 'tax')
                    <tr class="sum"><td colspan="4" style="text-align:right">VAT {{ $vatPct }}%</td><td class="c">AED</td><td class="r">{{ $money($quote->vat_amount) }}</td></tr>
                @endif
                <tr class="grand"><td colspan="4" style="text-align:right">{{ $mode === 'tax' ? 'TOTAL AMOUNT (INCL. VAT)' : 'TOTAL AMOUNT' }}</td><td class="c">AED</td><td class="r">{{ $money($total) }}</td></tr>
                <tr class="words"><td colspan="6">{{ \App\Models\Quotation::amountInWords($total) }}</td></tr>
            @endif
        </tbody>
    </table>

    @if($mode === 'tech' && $quote->technical_description)
        <table class="qd-box">
            <tr><th>TECHNICAL DESCRIPTION:</th></tr>
            <tr><td><p>{{ $quote->technical_description }}</p></td></tr>
        </table>
    @endif

    <table class="qd-box">
        <tr><th>TERMS AND CONDITIONS:</th></tr>
        <tr><td><p>{{ $quote->terms_conditions ?: 'Standard commercial terms apply.' }}</p></td></tr>
    </table>

    @if($priced)
        <table class="qd-box">
            <tr><th>PAYMENT TERMS:</th></tr>
            <tr>
                <td>
                    <p><span class="red">1. 50% Advance against security cheque and balance after completion of work.</span>
2. All invoices will be charged {{ $vatPct }}% VAT as per UAE law.
3. Payment Cheque or Bank Payee to: <b>RICH MOUNT CONTRACTING &amp; INTERIORS L.L.C.</b></p>
                </td>
            </tr>
        </table>
    @endif

    <table class="qd-box">
        <tr><th colspan="2">POINT OF CONTACT</th></tr>
        <tr><td class="k">Engineer Name:</td><td>{{ $engineer?->name ?? '—' }}</td></tr>
        <tr><td class="k">Position:</td><td>Project Engineer</td></tr>
        <tr><td class="k">Email:</td><td>{{ $engineer?->email ?? '—' }}</td></tr>
    </table>

    <div class="qd-foot">Office No. 05, Al Wasl Building, Street 47, Al Khor 2. Dubai UAE</div>
</div>
</body>
</html>
