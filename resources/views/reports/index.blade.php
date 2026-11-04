@extends('layouts.app')

@section('title', 'Financial Reports')
@section('page_id', 'reports')

@section('breadcrumbs')
    <svg class="ic sep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
    <span class="crumb current">Reports</span>
@endsection

@section('content')
<section class="page-head" id="pageHead">
    <div>
        <h1>Reports & Analytics</h1>
    
    </div>
    <div class="page-actions">
        <a class="btn btn-ghost" href="{{ route('reports.export', array_merge(['type' => $reportType], request()->query())) }}">
            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-download"/></svg>
            Export Excel
        </a>
        <a class="btn btn-ghost" href="{{ route('reports.pdf', array_merge(['type' => $reportType], request()->query())) }}">
            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-text"/></svg>
            Download PDF
        </a>
    </div>
</section>

<div class="dash-scroll">
    {{-- Report Navigation Tabs --}}
    <div class="table-tabs" style="display: flex; gap: 8px; margin-bottom: 0px;">
        <a href="{{ route('reports.index', ['type' => 'profitability']) }}"
           class="tab-link {{ $reportType === 'profitability' ? 'active' : '' }}"
           style="padding: 8px 16px; font-size: 12px; border-radius: 6px; text-decoration: none; color: {{ $reportType === 'profitability' ? '#fff' : 'var(--text)' }}; background: {{ $reportType === 'profitability' ? 'var(--primary)' : 'var(--bg-subtle)' }}; font-weight: 500; display: inline-flex; align-items: center; gap: 6px;">
            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-trending-up"/></svg>
            Project Profitability & Margins
        </a>
        <a href="{{ route('reports.index', ['type' => 'vat']) }}"
           class="tab-link {{ $reportType === 'vat' ? 'active' : '' }}"
           style="padding: 8px 16px; font-size: 12px; border-radius: 6px; text-decoration: none; color: {{ $reportType === 'vat' ? '#fff' : 'var(--text)' }}; background: {{ $reportType === 'vat' ? 'var(--primary)' : 'var(--bg-subtle)' }}; font-weight: 500; display: inline-flex; align-items: center; gap: 6px;">
            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-file-text"/></svg>
            UAE FTA VAT 201 Return
        </a>
        <a href="{{ route('reports.index', ['type' => 'cash']) }}"
           class="tab-link {{ $reportType === 'cash' ? 'active' : '' }}"
           style="padding: 8px 16px; font-size: 12px; border-radius: 6px; text-decoration: none; color: {{ $reportType === 'cash' ? '#fff' : 'var(--text)' }}; background: {{ $reportType === 'cash' ? 'var(--primary)' : 'var(--bg-subtle)' }}; font-weight: 500; display: inline-flex; align-items: center; gap: 6px;">
            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-coins"/></svg>
            Engineer Cash in Hand Ledgers
        </a>
    </div>

    @if($reportType === 'profitability')
        @php
            $totalContract = collect($data)->sum('contract_value');
            $totalInvoiced = collect($data)->sum('total_invoiced');
            $totalReceived = collect($data)->sum('total_received');
            $totalExpenses = collect($data)->sum('total_expenses');
            $totalOutstanding = collect($data)->sum('outstanding_amount');
            $totalProfit = collect($data)->sum('net_profit');
            $avgMargin = $totalInvoiced > 0 ? round(($totalProfit / $totalInvoiced) * 100, 2) : 0;
        @endphp

        <section class="kpi-grid five" aria-label="Project Financial KPIs">
            <div class="kpi static">
                <span class="kpi-top"><span class="kpi-icon"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-briefcase"/></svg></span></span>
                <span class="kpi-value">AED {{ number_format($totalContract, 2) }}</span>
                <span class="kpi-label">Contract Amount</span>
             
            </div>

            <div class="kpi static">
                <span class="kpi-top"><span class="kpi-icon"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-file-text"/></svg></span></span>
                <span class="kpi-value">AED {{ number_format($totalInvoiced, 2) }}</span>
                <span class="kpi-label">Total Invoiced</span>
          
            </div>

            <div class="kpi static">
                <span class="kpi-top"><span class="kpi-icon"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-coins"/></svg></span></span>
                <span class="kpi-value">AED {{ number_format($totalReceived, 2) }}</span>
                <span class="kpi-label">Total Received</span>
          
            </div>

            <div class="kpi static">
                <span class="kpi-top"><span class="kpi-icon"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-wallet"/></svg></span></span>
                <span class="kpi-value">AED {{ number_format($totalExpenses, 2) }}</span>
                <span class="kpi-label">Total Project Expenses</span>
         
            </div>

            <div class="kpi static">
                <span class="kpi-top"><span class="kpi-icon"><svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-trending-up"/></svg></span></span>
                <span class="kpi-value" style="color: {{ $totalProfit >= 0 ? '#10b981' : '#ef4444' }};">AED {{ number_format($totalProfit, 2) }}</span>
                <span class="kpi-label">Net Profit ({{ $avgMargin }}%)</span>
         
            </div>
        </section>

        <section class="card">
            <header class="card-head">
                <h2>Project Job-Costing & Profit Margin Analysis</h2>
             
            </header>

            <div class="table-wrap recent-wrap">
                <table class="table report-table">
                    <thead>
                        <tr>
                            <th>Project No</th>
                            <th>Project Title</th>
                            <th>Client</th>
                            <th>Branch</th>
                            <th>Status</th>
                            <th class="num">Contract (AED)</th>
                            <th class="num">Total Invoiced (AED)</th>
                            <th class="num">Total Received (AED)</th>
                            <th class="num">Total Expenses (AED)</th>
                            <th class="num">Outstanding (AED)</th>
                            <th class="num">Net Profit (AED)</th>
                            <th class="num">Profit Margin %</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($projectRows as $row)
                            <tr>
                                <td class="strong">
                                    <a class="report-project-id" href="{{ route('reports.project', $row['project_id']) }}">
                                        {{ $row['project_number'] }}
                                    </a>
                                </td>
                                <td><b><a class="report-project-name" href="{{ route('reports.project', $row['project_id']) }}">{{ $row['name'] }}</a></b></td>
                                <td>{{ $row['client'] }}</td>
                                <td>{{ $row['branch'] }}</td>
                                <td><span class="pill pill-info">{{ ucfirst($row['status']) }}</span></td>
                                <td class="num">{{ number_format($row['contract_value'], 2) }}</td>
                                <td class="num">{{ number_format($row['total_invoiced'], 2) }}</td>
                                <td class="num">{{ number_format($row['total_received'], 2) }}</td>
                                <td class="num">{{ number_format($row['total_expenses'], 2) }}</td>
                                <td class="num">{{ number_format($row['outstanding_amount'], 2) }}</td>
                                <td class="num" style="color: {{ $row['net_profit'] >= 0 ? '#10b981' : '#ef4444' }};"><b>{{ number_format($row['net_profit'], 2) }}</b></td>
                                <td class="num" style="color: {{ $row['profit_margin'] >= 15 ? '#10b981' : ($row['profit_margin'] >= 5 ? '#f59e0b' : '#ef4444') }};">
                                    <b>{{ number_format($row['profit_margin'], 2) }}%</b>
                                </td>
                                <td class="ta-c">
                                    <span class="row-actions">
                                        <a class="act view" href="{{ route('reports.project', $row['project_id']) }}" title="View project details" aria-label="View {{ $row['project_number'] }} details">
                                            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-eye"/></svg>
                                        </a>
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="ta-c" style="padding: 24px; color: var(--muted);">No project data available for financial analysis.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($projectRows->total() > 0)
                {{ $projectRows->links() }}
            @endif
        </section>

    @elseif($reportType === 'vat')
        {{-- Report 2: UAE FTA VAT Summary --}}
        <section class="card">
            <header class="card-head">
                <h2>
                    <span class="head-ic">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-file-text"/></svg>
                    </span>
                    Federal Tax Authority (FTA) — VAT 201 Return
                </h2>
                <span class="pill pill-info">{{ $data['tax_period'] }}</span>
            </header>

            <div >
                <p style="font-size: 12.5px; color: var(--muted); margin-bottom: 10px;">
                    Summary of Standard Rated supplies (5% UAE VAT) generated from client tax invoices and input VAT recoverable on commercial project expenses.
                </p>

                <table class="table" style="font-size: 12.5px; width: 100%;">
                    <thead>
                        <tr>
                            <th>VAT Return Box</th>
                            <th class="num">Amount (AED)</th>
                            <th class="num">VAT Amount (AED)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="background: rgba(16, 185, 129, 0.04);">
                            <td>
                                <b>Box 1: Standard Rated Supplies (Tax Invoices)</b>
                                <div style="font-size: 11px; color: var(--muted);">Output VAT collected on awarded contracting works</div>
                            </td>
                            <td class="num">{{ number_format($data['standard_rated_supplies'], 2) }}</td>
                            <td class="num" style="color: #10b981; font-weight: 600;">{{ number_format($data['output_vat'], 2) }}</td>
                        </tr>

                        <tr style="background: rgba(239, 68, 68, 0.04);">
                            <td>
                                <b>Box 9: Standard Rated Expenses (Purchases & Receipts)</b>
                                <div style="font-size: 11px; color: var(--muted);">Input VAT paid on site materials, fuel, and supplies</div>
                            </td>
                            <td class="num">{{ number_format($data['standard_rated_expenses'], 2) }}</td>
                            <td class="num" style="color: #ef4444; font-weight: 600;">{{ number_format($data['input_vat'], 2) }}</td>
                        </tr>

                        <tr style="border-top: 2px solid var(--border); background: var(--bg-subtle); font-size: 13.5px;">
                            <td>
                                <b>Box 14: Net VAT Payable to Federal Tax Authority</b>
                                <div style="font-size: 11px; color: var(--muted);">(Output VAT minus Input VAT Recoverable)</div>
                            </td>
                            <td class="num">—</td>
                            <td class="num" style="color: {{ $data['net_vat_payable'] >= 0 ? 'var(--primary)' : '#10b981' }}; font-weight: 700;">
                                AED {{ number_format($data['net_vat_payable'], 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

    @elseif($reportType === 'cash')
        {{-- Report 3: Engineer Cash in Hand Ledgers --}}
        <div style="display: flex; flex-direction: column; gap: 12px;">
            @forelse($data as $engLedger)
                <section class="card">
                    <header class="card-head" style="justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div class="user-avatar" style="width: 38px; height: 38px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                {{ substr($engLedger['engineer_name'], 0, 2) }}
                            </div>
                            <div>
                                <h2 style="margin: 0; font-size: 14px;">{{ $engLedger['engineer_name'] }}</h2>
                                <div style="font-size: 11px; color: var(--muted);">{{ $engLedger['branch'] }} &bull; {{ $engLedger['email'] }}</div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 20px; align-items: center;">
                            <div style="text-align: right;">
                                <div style="font-size: 10px; color: var(--muted); text-transform: uppercase;">Float Credited</div>
                                <div style="font-size: 12px; color: #10b981; font-weight: 600;">+ AED {{ number_format($engLedger['total_credited'], 2) }}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 10px; color: var(--muted); text-transform: uppercase;">Cash Spent</div>
                                <div style="font-size: 12px; color: #ef4444; font-weight: 600;">- AED {{ number_format($engLedger['total_spent'], 2) }}</div>
                            </div>
                            <div style="text-align: right; background: var(--bg-subtle); padding: 6px 12px; border-radius: 6px; border: 1px solid var(--border);">
                                <div style="font-size: 10px; color: var(--muted); text-transform: uppercase;">Cash in Hand</div>
                                <div style="font-size: 14px; font-weight: 700; color: var(--primary);">
                                    AED {{ number_format($engLedger['current_cash_balance'], 2) }}
                                </div>
                            </div>
                        </div>
                    </header>

                    <div class="table-wrap page">
                        <table class="table" style="font-size: 11px;">
                            <thead>
                                <tr>
                                    <th>Entry Type</th>
                                    <th>Date</th>
                                    <th>Reference No</th>
                                    <th>Particulars / Notes</th>
                                    <th class="num">Amount (AED)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($engLedger['history'] as $entry)
                                    <tr>
                                        <td>
                                            @if($entry['type'] === 'transfer_in')
                                                <span class="pill pill-success" style="font-size: 10px;">Float Received</span>
                                            @else
                                                <span class="pill pill-warning" style="font-size: 10px;">Cash Expense</span>
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($entry['date'])->format('d M Y') }}</td>
                                        <td class="strong">{{ $entry['reference'] }}</td>
                                        <td>{{ $entry['notes'] }}</td>
                                        <td class="num" style="color: {{ $entry['type'] === 'transfer_in' ? '#10b981' : '#ef4444' }}; font-weight: 600;">
                                            {{ $entry['type'] === 'transfer_in' ? '+' : '-' }} {{ number_format($entry['amount'], 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="ta-c" style="padding: 16px; color: var(--muted);">No transactions recorded in ledger.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $engLedger['history']->links() }}
                </section>
            @empty
                <section class="card">
                    <div style="padding: 32px; text-align: center; color: var(--muted);">No engineers found in system.</div>
                </section>
            @endforelse
        </div>
    @endif
</div>
@endsection
