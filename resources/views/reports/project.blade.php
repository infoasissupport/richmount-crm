@extends('layouts.app')

@section('title', 'Project Financial Report')
@section('page_id', 'reports-project')

@section('breadcrumbs')
    <svg class="ic sep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
    <a class="crumb" href="{{ route('reports.index') }}">Reports</a>
    <svg class="ic sep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
    <span class="crumb current">{{ $project->project_number }}</span>
@endsection

@section('content')
<section class="page-head" id="pageHead">
    <div>
        <h1>{{ $project->project_number }} — {{ $project->name }}</h1>
     
    </div>
    <div class="page-actions">
        <a class="btn btn-ghost" href="{{ route('reports.index') }}">
            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow-left"/></svg>
            Back to Reports
        </a>
        <button class="btn btn-primary" type="button" onclick="window.print()">
            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-printer"/></svg>
            Print Report
        </button>
    </div>
</section>

<div class="dash-scroll">
    @php
        $summary = $detail['summary'];
        $detailProject = $detail['project'];
        $categoryTotals = $detail['category_totals'];
        $incomeRows = $detail['income_rows'];
        $paymentRows = $detail['payment_rows'];
        $expenseRows = $detail['expense_rows'];
    @endphp



    <section class="card">
        <header class="card-head">
            <h2>
                <span class="head-ic"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-briefcase"/></svg></span>
                Project Financial Overview
            </h2>
            <span class="pill pill-{{ $detailProject->statusTone() }}">{{ $detailProject->statusLabel() }}</span>
        </header>

        <div class="table-wrap page">
            <table class="table project-overview-table" style="font-size: 12px;">
                <tbody>
                    <tr><th style="width: 220px;">Project Number</th><td>{{ $detailProject->project_number }}</td></tr>
                    <tr><th>Project Title</th><td>{{ $detailProject->name }}</td></tr>
                    <tr><th>Client</th><td>{{ $detailProject->client_name }}</td></tr>
                    <tr><th>Branch</th><td>{{ $detailProject->branch?->name ?? '—' }}</td></tr>
                    <tr><th>Contract Amount</th><td>AED {{ number_format($summary['contract_amount'], 2) }}</td></tr>
                    <tr><th>Total Invoiced</th><td>AED {{ number_format($summary['total_invoiced'], 2) }}</td></tr>
                    <tr><th>Total Received</th><td>AED {{ number_format($summary['total_received'], 2) }}</td></tr>
                    <tr><th>Total Expenses</th><td>AED {{ number_format($summary['total_expenses'], 2) }}</td></tr>
                    <tr><th>Outstanding Amount</th><td>AED {{ number_format($summary['outstanding_amount'], 2) }}</td></tr>
                    <tr><th>Contract Profit</th><td style="color: {{ $summary['contract_profit'] >= 0 ? '#10b981' : '#ef4444' }}; font-weight:700;">AED {{ number_format($summary['contract_profit'], 2) }}</td></tr>
                    <tr><th>Cash Position</th><td style="color: {{ $summary['cash_position'] >= 0 ? '#10b981' : '#ef4444' }}; font-weight:700;">AED {{ number_format($summary['cash_position'], 2) }}</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <header class="card-head">
            <h2>
                <span class="head-ic"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-receipt"/></svg></span>
                Project Income Details
            </h2>
        </header>

        <div class="table-wrap page">
            <table class="table" style="font-size: 11.5px;">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice Number</th>
                        <th>Description</th>
                        <th>Client</th>
                        <th class="num">Invoice Amount</th>
                        <th class="num">Tax Amount</th>
                        <th>Invoice Status</th>
                        <th>Payment Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($incomeRows as $row)
                        <tr>
                            <td>{{ $row['date'] }}</td>
                            <td><b>{{ $row['invoice_number'] }}</b></td>
                            <td>{{ $row['description'] }}</td>
                            <td>{{ $row['client'] }}</td>
                            <td class="num">AED {{ number_format($row['invoice_amount'], 2) }}</td>
                            <td class="num">AED {{ number_format($row['tax_amount'], 2) }}</td>
                            <td><span class="pill pill-info">{{ $row['invoice_status'] }}</span></td>
                            <td><span class="pill {{ $row['payment_status'] === 'Received' ? 'pill-success' : ($row['payment_status'] === 'Partially Received' ? 'pill-warning' : 'pill-gray') }}">{{ $row['payment_status'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="ta-c" style="padding: 20px; color: var(--muted);">No invoice activity recorded for this project.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $incomeRows->links() }}
    </section>

    <section class="card">
        <header class="card-head">
            <h2>
                <span class="head-ic"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-coins"/></svg></span>
                Project Expense Details
            </h2>
        </header>

        <div style=" display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px;">
            @foreach($detail['category_totals'] as $category)
                <div style="border:1px solid var(--line); border-radius:10px; padding:12px; background:#FAFAFB;">
                    <div style="font-size:11px; color:var(--muted); text-transform: uppercase;">{{ $category['label'] }}</div>
                    <div style="font-size:18px; font-weight:700; margin-top:4px;">AED {{ number_format($category['amount'], 2) }}</div>
                </div>
            @endforeach
        </div>

        <div class="table-wrap page">
            <table class="table" style="font-size: 11.5px;">
                <thead>
                    <tr>
                        <th>Expense Date</th>
                        <th>Expense Number</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Vendor / Supplier</th>
                        <th>Quantity</th>
                        <th>Unit</th>
                        <th class="num">Amount</th>
                        <th class="num">Tax</th>
                        <th>Payment Method</th>
                        <th>Receipt</th>
                        <th>Branch</th>
                        <th>Created By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenseRows as $row)
                        <tr>
                            <td>{{ $row['date'] }}</td>
                            <td><b>{{ $row['expense_number'] }}</b></td>
                            <td>{{ $row['category'] }}</td>
                            <td>{{ $row['description'] }}</td>
                            <td>{{ $row['vendor'] }}</td>
                            <td>{{ $row['quantity'] }}</td>
                            <td>{{ $row['unit'] }}</td>
                            <td class="num">AED {{ number_format($row['amount'], 2) }}</td>
                            <td class="num">AED {{ number_format($row['tax'], 2) }}</td>
                            <td>{{ $row['payment_method'] }}</td>
                            <td>
                                @if($row['receipt'])
                                    <a href="{{ $row['receipt'] }}" target="_blank" rel="noopener" style="color: var(--primary);">View</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $row['branch'] }}</td>
                            <td>{{ $row['created_by'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="13" class="ta-c" style="padding: 20px; color: var(--muted);">No expense records linked to this project.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $expenseRows->links() }}
    </section>

    <section class="card">
        <header class="card-head">
            <h2>
                <span class="head-ic"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-chart"/></svg></span>
                Project Cost Breakdown
            </h2>
        </header>

        <div style=" display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
            @php
                $costBreakdown = [
                    ['label' => 'Material Costs', 'amount' => collect($expenseRows)->where('category', 'Material Costs')->sum('amount')],
                    ['label' => 'Labour Costs', 'amount' => collect($expenseRows)->where('category', 'Labour Costs')->sum('amount')],
                    ['label' => 'Transportation Costs', 'amount' => collect($expenseRows)->where('category', 'Transportation Costs')->sum('amount')],
                    ['label' => 'Equipment / Tools Costs', 'amount' => collect($expenseRows)->where('category', 'Equipment / Tools Costs')->sum('amount')],
                    ['label' => 'Vendor / Subcontractor Costs', 'amount' => collect($expenseRows)->where('category', 'Vendor / Subcontractor Costs')->sum('amount')],
                    ['label' => 'Other Expenses', 'amount' => collect($expenseRows)->where('category', 'Other Expenses')->sum('amount')],
                ];
            @endphp

            @foreach($costBreakdown as $cost)
                <div style="padding: 14px; border:1px solid var(--line); border-radius:10px; background:#FAFAFB;">
                    <div style="font-size:11px; color:var(--muted); text-transform: uppercase;">{{ $cost['label'] }}</div>
                    <div style="font-size: 20px; font-weight:700; margin-top:6px;">AED {{ number_format($cost['amount'], 2) }}</div>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection
