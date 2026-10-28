<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $wcc->certificate_number }} - Work Completion Certificate</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; color: #121418; line-height: 1.5; margin: 0; padding: 15px; }
        .header-table { width: 100%; border-bottom: 3px solid #941C3C; padding-bottom: 12px; margin-bottom: 20px; }
        .logo-box { width: 55%; vertical-align: top; }
        .logo-box strong { font-size: 16px; color: #121418; display: block; margin-top: 4px; }
        .logo-box small { color: #666; font-size: 10px; }
        .title-box { width: 45%; text-align: right; vertical-align: top; }
        .title-box h1 { margin: 0; font-size: 18px; color: #941C3C; text-transform: uppercase; }
        .title-box small { color: #555; font-size: 11px; }

        .cert-box { border: 2px solid #941C3C; border-radius: 6px; padding: 20px; margin-bottom: 25px; background: #fffdfd; }
        .cert-heading { text-align: center; margin-bottom: 20px; }
        .cert-heading h2 { font-size: 16px; text-transform: uppercase; color: #121418; margin: 0 0 5px 0; letter-spacing: 1px; }
        .cert-heading p { margin: 0; color: #666; font-size: 11px; }

        .kv-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .kv-table td { padding: 8px 10px; border-bottom: 1px solid #eee; font-size: 11.5px; }
        .kv-table td.lbl { width: 30%; color: #666; font-weight: bold; text-transform: uppercase; font-size: 10px; background: #fafafa; }
        .kv-table td.val { width: 70%; color: #121418; }

        .statement { font-size: 11.5px; color: #222; margin: 20px 0; line-height: 1.6; text-align: justify; }

        .sign-table { width: 100%; margin-top: 40px; border-collapse: collapse; }
        .sign-table td { width: 50%; vertical-align: top; font-size: 11px; padding: 0 25px; }
        .sign-box { border: 1px dashed #bbb; border-radius: 4px; padding: 15px; height: 110px; text-align: center; }
        .sign-box small { color: #888; display: block; margin-top: 55px; }

        .footer { font-size: 9px; color: #888; text-align: center; margin-top: 30px; border-top: 1px solid #eee; padding-top: 8px; }
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
                <h1>WORK COMPLETION CERTIFICATE</h1>
                <small>Certificate No: <b>{{ $wcc->certificate_number }}</b></small><br>
                <small>Issue Date: {{ $wcc->certificate_date->format('d M Y') }}</small>
            </td>
        </tr>
    </table>

    <div class="cert-box">
        <div class="cert-heading">
            <h2>CERTIFICATE OF PRACTICAL COMPLETION & HANDOVER</h2>
            <p>This is to certify that the contracted works described herein have been completed to satisfaction</p>
        </div>

        <table class="kv-table">
            <tr>
                <td class="lbl">Project Number</td>
                <td class="val"><b>{{ $project->project_number }}</b></td>
            </tr>
            <tr>
                <td class="lbl">Project Name</td>
                <td class="val"><b>{{ $project->name }}</b></td>
            </tr>
            <tr>
                <td class="lbl">Customer / Employer</td>
                <td class="val">{{ $project->client_name }}</td>
            </tr>
            <tr>
                <td class="lbl">Client PO Reference</td>
                <td class="val">{{ $project->client_po_number ?? 'N/A' }} (Dated: {{ $project->client_po_date ? $project->client_po_date->format('d M Y') : '—' }})</td>
            </tr>
            <tr>
                <td class="lbl">Contract Value</td>
                <td class="val">AED {{ number_format($project->contract_value, 2) }}</td>
            </tr>
            <tr>
                <td class="lbl">Completion Date</td>
                <td class="val"><b>{{ $wcc->completion_date->format('d M Y') }}</b></td>
            </tr>
            <tr>
                <td class="lbl">Retention Retained</td>
                <td class="val">{{ number_format($wcc->retention_pct, 1) }}% (AED {{ number_format($wcc->retention_amount, 2) }})</td>
            </tr>
            <tr>
                <td class="lbl">Scope of Completed Works</td>
                <td class="val">{{ $wcc->work_description }}</td>
            </tr>
        </table>

        <p class="statement">
            The Contractor, <b>Rich Mount Contracting LLC</b>, has completed the works defined in the contract agreement and approved variation orders. The works have been inspected, tested, and found to comply with relevant municipality, civil defence, and authority standards. Defects Liability Period (DLP) commences from the completion date specified above.
        </p>

        <table class="sign-table">
            <tr>
                <td>
                    <div class="sign-box">
                        <strong>CONTRACTOR SIGNATURE</strong><br>
                        <span>{{ $engineer ? $engineer->name : 'Project Engineer' }}</span>
                        <small>Rich Mount Contracting LLC (Stamp & Signature)</small>
                    </div>
                </td>
                <td>
                    <div class="sign-box">
                        <strong>CLIENT ACCEPTANCE</strong><br>
                        <span>{{ $wcc->client_representative_name ?? 'Client Representative' }}</span><br>
                        <small style="margin-top:40px;">{{ $project->client_name }} (Authorized Signatory)</small>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Official document &bull; Rich Mount ERP System &bull; Generated on {{ now()->format('d M Y, h:i A') }}
    </div>
</body>
</html>
