<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project Financial Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; margin: 24px; }
        h1 { font-size: 22px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
        .num { text-align: right; }
        .kpis { display: grid; grid-template-columns: repeat(5, minmax(120px, 1fr)); gap: 10px; margin-bottom: 16px; }
        .kpi { border: 1px solid #e5e7eb; padding: 10px; border-radius: 8px; }
        .label { font-size: 9px; color: #6b7280; text-transform: uppercase; }
        .value { font-size: 16px; font-weight: 700; margin-top: 4px; }
    </style>
</head>
<body>
    <h1>Project Financial Report</h1>

    @php
        $totalContract = collect($data)->sum('contract_value');
        $totalInvoiced = collect($data)->sum('total_invoiced');
        $totalReceived = collect($data)->sum('total_received');
        $totalExpenses = collect($data)->sum('total_expenses');
        $totalProfit = collect($data)->sum('net_profit');
        $avgMargin = $totalInvoiced > 0 ? round(($totalProfit / $totalInvoiced) * 100, 2) : 0;
    @endphp

    <div class="kpis">
        <div class="kpi"><div class="label">Contract</div><div class="value">AED {{ number_format($totalContract, 2) }}</div></div>
        <div class="kpi"><div class="label">Invoiced</div><div class="value">AED {{ number_format($totalInvoiced, 2) }}</div></div>
        <div class="kpi"><div class="label">Received</div><div class="value">AED {{ number_format($totalReceived, 2) }}</div></div>
        <div class="kpi"><div class="label">Expenses</div><div class="value">AED {{ number_format($totalExpenses, 2) }}</div></div>
        <div class="kpi"><div class="label">Net Profit</div><div class="value">AED {{ number_format($totalProfit, 2) }}</div></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Project No</th>
                <th>Project Title</th>
                <th>Client</th>
                <th>Branch</th>
                <th>Status</th>
                <th class="num">Contract</th>
                <th class="num">Invoiced</th>
                <th class="num">Received</th>
                <th class="num">Expenses</th>
                <th class="num">Outstanding</th>
                <th class="num">Net Profit</th>
                <th class="num">Margin %</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
                <tr>
                    <td>{{ $row['project_number'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['client'] }}</td>
                    <td>{{ $row['branch'] }}</td>
                    <td>{{ $row['status'] }}</td>
                    <td class="num">{{ number_format($row['contract_value'], 2) }}</td>
                    <td class="num">{{ number_format($row['total_invoiced'], 2) }}</td>
                    <td class="num">{{ number_format($row['total_received'], 2) }}</td>
                    <td class="num">{{ number_format($row['total_expenses'], 2) }}</td>
                    <td class="num">{{ number_format($row['outstanding_amount'], 2) }}</td>
                    <td class="num">{{ number_format($row['net_profit'], 2) }}</td>
                    <td class="num">{{ number_format($row['profit_margin'], 2) }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
