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
        <p class="sub">Audited financial summaries, project margins, petty cash ledgers, and UAE VAT returns.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-ghost" href="{{ route('reports.export', ['type' => $reportType]) }}">
            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-download"/></svg>
            Export CSV
        </a>
    </div>
</section>

<div class="dash-scroll">
    {{-- Report Navigation Tabs --}}
    <div class="table-tabs" style="display: flex; gap: 8px; margin-bottom: 20px;">
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
        {{-- Report 1: Project Profitability --}}
        @php
            $totalContract = collect($data)->sum('contract_value');
            $totalInvoiced = collect($data)->sum('invoiced');
            $totalCost = collect($data)->sum('total_cost');
            $totalProfit = collect($data)->sum('profit');
            $avgMargin = $totalContract > 0 ? round(($totalProfit / $totalContract) * 100, 1) : 0;
        @endphp

        <section class="kpi-grid four" aria-label="Profitability KPIs">
            <div class="kpi static">
                <span class="kpi-top">
                    <span class="kpi-icon">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-briefcase"/></svg>
                    </span>
                </span>
                <span class="kpi-value">AED {{ number_format($totalContract, 2) }}</span>
                <span class="kpi-label">Total Contract Value</span>
                <span class="kpi-note">{{ count($data) }} active & completed projects</span>
            </div>

            <div class="kpi static">
                <span class="kpi-top">
                    <span class="kpi-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-file-text"/></svg>
                    </span>
                </span>
                <span class="kpi-value">AED {{ number_format($totalInvoiced, 2) }}</span>
                <span class="kpi-label">Total Invoiced</span>
                <span class="kpi-note">Progress & final billings</span>
            </div>

            <div class="kpi static">
                <span class="kpi-top">
                    <span class="kpi-icon" style="background: rgba(239, 68, 68, 0.12); color: #ef4444;">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-wallet"/></svg>
                    </span>
                </span>
                <span class="kpi-value">AED {{ number_format($totalCost, 2) }}</span>
                <span class="kpi-label">Total Job Cost</span>
                <span class="kpi-note">Allocated expenses & purchases</span>
            </div>

            <div class="kpi static">
                <span class="kpi-top">
                    <span class="kpi-icon" style="background: rgba(59, 130, 246, 0.12); color: #3b82f6;">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-trending-up"/></svg>
                    </span>
                </span>
                <span class="kpi-value">AED {{ number_format($totalProfit, 2) }}</span>
                <span class="kpi-label">Net Gross Profit ({{ $avgMargin }}%)</span>
                <span class="kpi-note">Average realized margin</span>
            </div>
        </section>

        <section class="card" style="margin-top: 20px;">
            <header class="card-head">
                <h2>Project Job-Costing & Profit Margin Analysis</h2>
            </header>

            <div class="table-wrap">
                <table class="table" style="font-size: 11.5px;">
                    <thead>
                        <tr>
                            <th>Project No</th>
                            <th>Project Title</th>
                            <th>Client</th>
                            <th>Branch</th>
                            <th>Status</th>
                            <th class="num">Contract (AED)</th>
                            <th class="num">Invoiced (AED)</th>
                            <th class="num">Total Cost (AED)</th>
                            <th class="num">Profit (AED)</th>
                            <th class="num">Margin %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $row)
                            <tr>
                                <td class="strong">
                                    <a href="{{ route('projects.show', $row['project_id']) }}" style="color: var(--primary); text-decoration: none;">
                                        {{ $row['project_number'] }}
                                    </a>
                                </td>
                                <td><b>{{ $row['name'] }}</b></td>
                                <td>{{ $row['client'] }}</td>
                                <td>{{ $row['branch'] }}</td>
                                <td><span class="pill pill-info">{{ ucfirst($row['status']) }}</span></td>
                                <td class="num">{{ number_format($row['contract_value'], 2) }}</td>
                                <td class="num">{{ number_format($row['invoiced'], 2) }}</td>
                                <td class="num">{{ number_format($row['total_cost'], 2) }}</td>
                                <td class="num" style="color: {{ $row['profit'] >= 0 ? '#10b981' : '#ef4444' }};">
                                    <b>{{ number_format($row['profit'], 2) }}</b>
                                </td>
                                <td class="num" style="color: {{ $row['margin_pct'] >= 15 ? '#10b981' : ($row['margin_pct'] >= 5 ? '#f59e0b' : '#ef4444') }};">
                                    <b>{{ $row['margin_pct'] }}%</b>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="ta-c" style="padding: 24px; color: var(--muted);">No project data available for margin analysis.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    @elseif($reportType === 'vat')
        {{-- Report 2: UAE FTA VAT Summary --}}
        <section class="card" style="max-width: 800px; margin: 0 auto;">
            <header class="card-head">
                <h2>
                    <span class="head-ic">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-file-text"/></svg>
                    </span>
                    Federal Tax Authority (FTA) — VAT 201 Return
                </h2>
                <span class="pill pill-info">{{ $data['tax_period'] }}</span>
            </header>

            <div style="padding: 20px;">
                <p style="font-size: 12.5px; color: var(--muted); margin-bottom: 20px;">
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
        <div style="display: flex; flex-direction: column; gap: 20px;">
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

                    <div class="table-wrap">
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
