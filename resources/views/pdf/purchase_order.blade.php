<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $po->po_number }} - Purchase Order</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; color: #121418; line-height: 1.4; margin: 0; padding: 15px; }
        .header-table { width: 100%; border-bottom: 3px solid #941C3C; padding-bottom: 12px; margin-bottom: 15px; }
        .logo-box { width: 55%; vertical-align: top; }
        .logo-box strong { font-size: 16px; color: #121418; display: block; margin-top: 4px; }
        .logo-box small { color: #666; font-size: 10px; }
        .title-box { width: 45%; text-align: right; vertical-align: top; }
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

        .sign-table { width: 100%; margin-top: 30px; border-collapse: collapse; }
        .sign-table td { width: 50%; vertical-align: bottom; font-size: 11px; padding: 0 20px; }
        .sign-line { border-top: 1px solid #333; margin-top: 50px; padding-top: 5px; text-align: center; }
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
                <h1>PURCHASE ORDER</h1>
                <small>PO Number: <b>{{ $po->po_number }}</b></small><br>
                <small>Date: {{ $po->po_date->format('d M Y') }}</small>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td>
                <small>Supplier / Vendor</small>
                <b>{{ $po->vendor_name }}</b>
                @if($po->vendor_trn)
                    <span>TRN: {{ $po->vendor_trn }}</span><br>
                @endif
                @if($po->vendor_contact)
                    <span>Contact: {{ $po->vendor_contact }}</span>
                @endif
            </td>
            <td>
                <small>Project Reference</small>
                <b>{{ $project->project_number }}</b>
                <span>{{ $project->name }}</span><br>
                <small style="margin-top:4px;">Client: {{ $project->client_name }}</small>
            </td>
            <td>
                <small>Delivery & Details</small>
                <b>{{ $po->delivery_date ? $po->delivery_date->format('d M Y') : 'Immediate / Per schedule' }}</b>
                <span>Status: <b style="color:#941C3C;">{{ strtoupper($po->status) }}</b></span><br>
                <span>Prepared by: {{ $po->preparedBy->name }}</span>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 55%;">Item Description</th>
                <th class="num" style="width: 10%;">Qty</th>
                <th style="width: 10%;">Unit</th>
                <th class="num" style="width: 10%;">Unit Rate</th>
                <th class="num" style="width: 10%;">Total (AED)</th>
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
            <td>Subtotal:</td>
            <td class="num">AED {{ number_format($po->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td>VAT (5%):</td>
            <td class="num">AED {{ number_format($po->vat_amount, 2) }}</td>
        </tr>
        <tr class="grand">
            <td>Grand Total:</td>
            <td class="num">AED {{ number_format($po->grand_total, 2) }}</td>
        </tr>
    </table>

    @if($po->terms)
        <div class="section-title">Terms & Conditions</div>
        <p class="section-text">{{ $po->terms }}</p>
    @endif

    <table class="sign-table">
        <tr>
            <td>
                <div class="sign-line">
                    <b>Prepared By: {{ $po->preparedBy->name }}</b><br>
                    <small>Rich Mount Contracting LLC</small>
                </div>
            </td>
            <td>
                <div class="sign-line">
                    <b>Authorized Signatory / Approver</b><br>
                    <small>Finance & Commercial Department</small>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Generated electronically by Rich Mount ERP on {{ now()->format('d M Y, h:i A') }} &bull; Page 1 of 1
    </div>
</body>
</html>
