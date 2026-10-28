@extends('layouts.app')

@section('title', 'Expenses')
@section('page_id', 'expenses')

@section('breadcrumbs')
    <svg class="ic sep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
    <span class="crumb current">Expenses</span>
@endsection

@section('content')
<section class="page-head" id="pageHead">
    <div>
        <h1>Expenses</h1>
        <p class="sub">Cash in hand, cash and card expenses, internal transfers and customer cash payments.</p>
    </div>
    <div class="page-actions">
        @can('transfers.create')
        <button class="btn btn-ghost" type="button" onclick="openTransferModal()">
            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-refresh"/></svg>
            New Transfer
        </button>
        @endcan

        @can('payments.create')
        <button class="btn btn-ghost" type="button" onclick="openCustomerPaymentModal()">
            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-coins"/></svg>
            Customer Payment
        </button>
        @endcan

        @can('expenses.create')
        <button class="btn btn-primary" type="button" onclick="openExpenseModal()">
            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-scan"/></svg>
            Add Expense / Scan Receipt
        </button>
        @endcan
    </div>
</section>

<div class="dash-scroll">
    {{-- Key Figures --}}
    <section class="kpi-grid four" id="kpis" aria-label="Key figures">
        <div class="kpi static">
            <span class="kpi-top">
                <span class="kpi-icon">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-coins"/></svg>
                </span>
            </span>
            <span class="kpi-value">AED {{ number_format($kpis['cash_in_hand'], 2) }}</span>
            <span class="kpi-label">Cash in Hand</span>
            <span class="kpi-note">Across all engineers</span>
        </div>

        <div class="kpi static">
            <span class="kpi-top">
                <span class="kpi-icon">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-wallet"/></svg>
                </span>
            </span>
            <span class="kpi-value">AED {{ number_format($kpis['cash_expense'], 2) }}</span>
            <span class="kpi-label">Cash Expenses</span>
            <span class="kpi-note">Petty cash spent</span>
        </div>

        <div class="kpi static">
            <span class="kpi-top">
                <span class="kpi-icon">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-credit-card"/></svg>
                </span>
            </span>
            <span class="kpi-value">AED {{ number_format($kpis['card_expense'], 2) }}</span>
            <span class="kpi-label">Card Expenses</span>
            <span class="kpi-note">Company cards</span>
        </div>

        <div class="kpi static">
            <span class="kpi-top">
                <span class="kpi-icon">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-refresh"/></svg>
                </span>
            </span>
            <span class="kpi-value">AED {{ number_format($kpis['transfers'], 2) }}</span>
            <span class="kpi-label">Total Transfers</span>
            <span class="kpi-note">Accountant float disbursed</span>
        </div>
    </section>

    {{-- Expense History Table with Status Tabs --}}
    <section class="card" id="expensesTable">
        <header class="card-head">
            <h2>
                <span class="head-ic">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-receipt"/></svg>
                </span>
                Expense History
            </h2>

            <div class="card-actions">
                <form method="GET" action="{{ route('expenses.index') }}" style="display: flex; gap: 8px;">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <input class="input sm" type="search" name="search" placeholder="Search expenses..." value="{{ request('search') }}" style="width: 220px;">
                </form>
            </div>
        </header>

        {{-- Tabs --}}
        <div class="table-tabs" style="display: flex; gap: 8px; border-bottom: 1px solid var(--border); padding: 0 16px 8px 16px; margin-bottom: 12px; overflow-x: auto;">
            @php
                $expenseTabs = [
                    'all' => 'All',
                    'cash' => 'Cash',
                    'card' => 'Card',
                    'pending' => 'Pending',
                    'approved' => 'Approved / Cleared',
                    'rejected' => 'Rejected',
                ];
            @endphp
            @foreach($expenseTabs as $tKey => $tLabel)
                <a href="{{ route('expenses.index', ['tab' => $tKey, 'search' => request('search')]) }}"
                   class="tab-link {{ $tab === $tKey ? 'active' : '' }}"
                   style="padding: 6px 14px; font-size: 11.5px; border-radius: 6px; text-decoration: none; color: {{ $tab === $tKey ? '#fff' : 'var(--text)' }}; background: {{ $tab === $tKey ? 'var(--primary)' : 'var(--bg-subtle)' }}; font-weight: 500;">
                    {{ $tLabel }}
                </a>
            @endforeach
        </div>

        <div class="table-wrap">
            <table class="table" style="font-size: 11.5px;">
                <thead>
                    <tr>
                        <th>Exp No</th>
                        <th>Date</th>
                        <th>User / Engineer</th>
                        <th>Project</th>
                        <th>Category</th>
                        <th>Vendor</th>
                        <th>Type</th>
                        <th class="num">Amount (AED)</th>
                        <th>Status</th>
                        <th>Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $exp)
                        <tr>
                            <td class="strong">{{ $exp->expense_number }}</td>
                            <td>{{ $exp->date->format('d M Y') }}</td>
                            <td><b>{{ $exp->user->name }}</b></td>
                            <td>
                                @if($exp->project)
                                    <a href="{{ route('projects.show', $exp->project) }}" style="color: var(--primary); text-decoration: none;">
                                        {{ $exp->project->project_number }}
                                    </a>
                                @else
                                    <span style="color: var(--muted);">General / Overhead</span>
                                @endif
                            </td>
                            <td><span class="pill pill-gray">{{ ucfirst($exp->category) }}</span></td>
                            <td>{{ $exp->vendor_name ?: '&mdash;' }}</td>
                            <td>
                                <span class="pill pill-{{ $exp->type === 'cash' ? 'blue' : 'purple' }}">
                                    {{ ucfirst($exp->type) }}
                                </span>
                            </td>
                            <td class="num"><b>{{ number_format($exp->grand_total, 2) }}</b></td>
                            <td>
                                <span class="pill pill-{{ $exp->statusTone() }}" title="{{ $exp->rejection_reason }}">
                                    <i></i>{{ ucfirst($exp->status) }}
                                </span>
                            </td>
                            <td>
                                @if($exp->receipt_file)
                                    <a class="pill pill-gray" href="{{ asset('storage/' . $exp->receipt_file) }}" target="_blank" style="text-decoration:none;">
                                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-text"/></svg>
                                        Receipt
                                    </a>
                                @else
                                    <span style="color: var(--muted);">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="text-align: center; color: var(--muted); padding: 25px;">
                                No expenses found for this selection.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($expenses->hasPages())
            <div style="padding: 12px 16px;">
                {{ $expenses->withQueryString()->links() }}
            </div>
        @endif
    </section>

    {{-- Internal Transfers Table --}}
    <section class="card" id="transfersTable" style="margin-top: 20px;">
        <header class="card-head">
            <h2>
                <span class="head-ic">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-refresh"/></svg>
                </span>
                Internal Transfers
            </h2>
            @can('transfers.create')
            <button class="btn btn-primary btn-sm" type="button" onclick="openTransferModal()">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-plus"/></svg>
                New Transfer
            </button>
            @endcan
        </header>

        <p class="lock-note" style="margin: 0 16px 12px 16px; font-size: 11.5px; color: var(--muted);">
            Create Transfer &rarr; Pending &rarr; Accountant Approval &rarr; Approved &rarr; Cash in Hand updated. Submitted transfers can only be edited by the Accountant.
        </p>

        <div class="table-wrap">
            <table class="table" style="font-size: 11.5px;">
                <thead>
                    <tr>
                        <th>Transfer No</th>
                        <th>Date</th>
                        <th>From Account</th>
                        <th>Recipient Engineer</th>
                        <th>Method</th>
                        <th class="num">Amount (AED)</th>
                        <th>Status</th>
                        <th class="ta-c">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $tr)
                        <tr>
                            <td class="strong">{{ $tr->transfer_number }}</td>
                            <td>{{ $tr->date->format('d M Y') }}</td>
                            <td>{{ $tr->fromUser?->name ?? 'Main Company Cash' }}</td>
                            <td><b>{{ $tr->toUser->name }}</b></td>
                            <td>{{ ucfirst(str_replace('_', ' ', $tr->payment_method)) }}</td>
                            <td class="num"><b>{{ number_format($tr->amount, 2) }}</b></td>
                            <td><span class="pill pill-{{ $tr->statusTone() }}">{{ ucfirst($tr->status) }}</span></td>
                            <td class="ta-c">
                                @if($tr->status === 'pending')
                                    @can('transfers.approve')
                                        <div style="display: flex; gap: 4px; justify-content: center;">
                                            <form action="{{ route('finance.transfers.approve', $tr) }}" method="POST" style="display:inline;">
                                                @csrf
                                                <button class="btn btn-success btn-sm" type="submit">Approve</button>
                                            </form>
                                            <button class="btn btn-ghost btn-sm" type="button" onclick="openRejectTransferModal({{ json_encode($tr) }})">Reject</button>
                                            @can('transfers.edit')
                                                <button class="act edit" type="button" onclick="openEditTransferModal({{ json_encode($tr) }})" title="Edit Transfer">
                                                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-edit"/></svg>
                                                </button>
                                            @endcan
                                        </div>
                                    @else
                                        <span class="lock-note" style="color: var(--muted); font-size: 11px;">
                                            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-lock"/></svg>
                                            Pending Approval
                                        </span>
                                    @endcan
                                @else
                                    <span style="color: var(--muted);">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--muted); padding: 20px;">
                                No internal transfers recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Two-col grid: Left (Cash in Hand by Engineer), Right (Customer Payments) --}}
    <div class="two-col" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
        {{-- Cash in Hand by Engineer --}}
        <section class="card" id="cashTable">
            <header class="card-head">
                <h2>
                    <span class="head-ic">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-coins"/></svg>
                    </span>
                    Cash in Hand by Engineer
                </h2>
            </header>
            <p class="lock-note" style="margin: 0 16px 10px 16px; font-size: 11px; color: var(--muted);">
                Opening cash + transfers in − cash expenses. Click view for the ledger.
            </p>

            <div class="table-wrap">
                <table class="table" style="font-size: 11.5px;">
                    <thead>
                        <tr>
                            <th>Engineer</th>
                            <th>Branch</th>
                            <th>Zoho Petty Cash</th>
                            <th class="num">Balance (AED)</th>
                            <th class="ta-c">Ledger</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($engineers as $eng)
                            <tr>
                                <td><b>{{ $eng->name }}</b></td>
                                <td><span class="pill pill-gray">{{ $eng->branch->short_name }}</span></td>
                                <td><small style="color: var(--muted);">{{ $eng->zoho_petty_cash_account_id ?: 'Default' }}</small></td>
                                <td class="num"><b>AED {{ number_format($eng->cash_balance, 2) }}</b></td>
                                <td class="ta-c">
                                    <button class="act view" type="button" onclick="showLedger({{ $eng->id }}, '{{ addslashes($eng->name) }}')" title="View Ledger">
                                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-eye"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Customer Payments Received --}}
        <section class="card" id="paymentsTable">
            <header class="card-head">
                <h2>
                    <span class="head-ic">
                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-wallet"/></svg>
                    </span>
                    Customer Payments
                </h2>
            </header>

            <div class="table-wrap">
                <table class="table" style="font-size: 11.5px;">
                    <thead>
                        <tr>
                            <th>Ref</th>
                            <th>Date</th>
                            <th>Customer / Project</th>
                            <th>Method</th>
                            <th class="num">Amount (AED)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $pay)
                            <tr>
                                <td class="strong">{{ $pay->payment_reference }}</td>
                                <td>{{ $pay->payment_date->format('d M Y') }}</td>
                                <td>
                                    <b>{{ $pay->invoice?->client_name ?? 'Client' }}</b><br>
                                    <small style="color: var(--muted);">{{ $pay->invoice?->project?->project_number }}</small>
                                </td>
                                <td>{{ ucfirst(str_replace('_', ' ', $pay->payment_method)) }}</td>
                                <td class="num"><b>{{ number_format($pay->amount, 2) }}</b></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--muted); padding: 20px;">
                                    No customer payments recorded in this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

{{-- Add Expense / Scan Receipt Modal --}}
<div class="modal-wrap" id="expenseModal" style="display: none;">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="expenseModalTitle">
        <div class="modal-head">
            <div>
                <h3 id="expenseModalTitle">Record Expense</h3>
                <p>Log petty cash or card expense with receipt verification</p>
            </div>
            <button class="icon-btn" type="button" onclick="closeExpenseModal()" aria-label="Close">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>

        <form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div class="form-grid">
                    <div class="field">
                        <label class="label req" for="expProject">Project</label>
                        <select class="select" id="expProject" name="project_id">
                            <option value="">General Overhead (No Project)</option>
                            @foreach($openProjects as $op)
                                <option value="{{ $op->id }}">{{ $op->project_number }} &bull; {{ $op->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label class="label req" for="expCat">Category</label>
                        <select class="select" id="expCat" name="category" required>
                            <option value="materials">Materials</option>
                            <option value="transport">Transport / Delivery</option>
                            <option value="labor">Daily Labor</option>
                            <option value="meals">Site Meals</option>
                            <option value="tools">Tools & Equipment</option>
                            <option value="fuel">Fuel & Petrol</option>
                            <option value="permits">Municipality & Permits</option>
                            <option value="miscellaneous">Miscellaneous</option>
                        </select>
                    </div>

                    <div class="field">
                        <label class="label req" for="expType">Payment Method</label>
                        <select class="select" id="expType" name="type" required>
                            <option value="cash">Petty Cash (Cash in Hand)</option>
                            <option value="card">Company Debit / Credit Card</option>
                        </select>
                    </div>

                    <div class="field">
                        <label class="label req" for="expDate">Expense Date</label>
                        <input class="input" id="expDate" name="date" type="date" required value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="field">
                        <label class="label req" for="expSubtotal">Subtotal (AED)</label>
                        <input class="input" id="expSubtotal" name="subtotal" type="number" step="0.01" min="0.01" required placeholder="0.00">
                    </div>

                    <div class="field">
                        <label class="label" for="expVat">VAT Rate</label>
                        <select class="select" id="expVat" name="vat_rate">
                            <option value="0.05">5% UAE VAT</option>
                            <option value="0">0% (Zero-rated / Exempt)</option>
                        </select>
                    </div>

                    <div class="field">
                        <label class="label" for="expVendor">Vendor / Merchant</label>
                        <input class="input" id="expVendor" name="vendor_name" type="text" placeholder="e.g. Ace Hardware LLC">
                    </div>

                    <div class="field">
                        <label class="label" for="expBill">Receipt / Bill Number</label>
                        <input class="input" id="expBill" name="bill_number" type="text" placeholder="e.g. REC-8821">
                    </div>

                    <div class="field span-2">
                        <label class="label" for="expReceipt">Upload Receipt Scan / Photo (OCR)</label>
                        <input class="input" id="expReceipt" name="receipt" type="file" accept=".pdf,.png,.jpg,.jpeg">
                    </div>

                    <div class="field span-2">
                        <label class="label" for="expDesc">Description & Justification</label>
                        <textarea class="textarea" id="expDesc" name="description" rows="2" placeholder="Purpose of expense..."></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeExpenseModal()">Cancel</button>
                <button class="btn btn-primary" type="submit">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                    Submit Expense
                </button>
            </div>
        </form>
    </div>
</div>

{{-- New Transfer Modal --}}
<div class="modal-wrap" id="transferModal" style="display: none;">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="transferModalTitle">
        <div class="modal-head">
            <div>
                <h3 id="transferModalTitle">Internal Petty Cash Transfer</h3>
                <p>Disburse petty cash float to project engineer</p>
            </div>
            <button class="icon-btn" type="button" onclick="closeTransferModal()" aria-label="Close">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>

        <form action="{{ route('transfers.store') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-grid">
                    <div class="field span-2">
                        <label class="label req" for="trToUser">Recipient Engineer</label>
                        <select class="select" id="trToUser" name="to_user_id" required>
                            @foreach($engineers as $eng)
                                <option value="{{ $eng->id }}">{{ $eng->name }} (Balance: AED {{ number_format($eng->cash_balance, 2) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field span-2">
                        <label class="label req" for="trAmt">Transfer Amount (AED)</label>
                        <input class="input" id="trAmt" name="amount" type="number" step="1" min="1" required placeholder="e.g. 5000">
                    </div>

                    <div class="field">
                        <label class="label req" for="trMethod">Method</label>
                        <select class="select" id="trMethod" name="payment_method" required>
                            <option value="cash">Cash in Envelope</option>
                            <option value="bank_transfer">Direct Bank Transfer</option>
                            <option value="cheque">Company Cheque</option>
                        </select>
                    </div>

                    <div class="field">
                        <label class="label" for="trRef">Reference Number</label>
                        <input class="input" id="trRef" name="reference_number" type="text" placeholder="e.g. CHQ-9902">
                    </div>

                    <div class="field span-2">
                        <label class="label" for="trNotes">Notes & Remarks</label>
                        <textarea class="textarea" id="trNotes" name="notes" rows="2" placeholder="Weekly petty cash replenishment..."></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeTransferModal()">Cancel</button>
                <button class="btn btn-primary" type="submit">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                    Submit Transfer
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Reject Transfer Modal --}}
<div class="modal-wrap" id="rejectTransferModal" style="display: none;">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="rejectTrTitle">
        <div class="modal-head">
            <div>
                <h3 id="rejectTrTitle">Reject Transfer</h3>
                <p>Provide revision reason to engineer</p>
            </div>
            <button class="icon-btn" type="button" onclick="closeRejectTransferModal()" aria-label="Close">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>

        <form id="rejectTransferForm" action="" method="POST">
            @csrf
            <div class="modal-body">
                <div class="field">
                    <label class="label req" for="trReason">Rejection Reason</label>
                    <textarea class="textarea" id="trReason" name="reason" rows="3" required placeholder="e.g. Previous cash float of AED 4,500 must be cleared first before issuing new transfer."></textarea>
                </div>
            </div>

            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeRejectTransferModal()">Cancel</button>
                <button class="btn btn-primary" type="submit" style="background: var(--danger, #dc2626);">
                    Reject Transfer
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Engineer Cash Ledger Modal --}}
<div class="modal-wrap" id="ledgerModal" style="display: none;">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="ledgerModalTitle">
        <div class="modal-head">
            <div>
                <h3 id="ledgerModalTitle">Cash in Hand Ledger</h3>
                <p id="ledgerModalSub">Running cash balance ledger</p>
            </div>
            <button class="icon-btn" type="button" onclick="closeLedgerModal()" aria-label="Close">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>

        <div class="modal-body">
            <div class="table-wrap">
                <table class="table" style="font-size: 11.5px;">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Transaction</th>
                            <th>Reference</th>
                            <th>Description</th>
                            <th class="num">In (AED)</th>
                            <th class="num">Out (AED)</th>
                        </tr>
                    </thead>
                    <tbody id="ledgerBody">
                        <tr><td colspan="6" style="text-align: center; color: var(--muted); padding: 20px;">Loading ledger...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal-foot">
            <button class="btn btn-primary" type="button" onclick="closeLedgerModal()">Close</button>
        </div>
    </div>
</div>

<script>
    function openExpenseModal() {
        document.getElementById('expenseModal').style.display = 'grid';
    }
    function closeExpenseModal() {
        document.getElementById('expenseModal').style.display = 'none';
    }

    function openTransferModal() {
        document.getElementById('transferModal').style.display = 'grid';
    }
    function closeTransferModal() {
        document.getElementById('transferModal').style.display = 'none';
    }

    function openRejectTransferModal(transfer) {
        document.getElementById('rejectTransferForm').action = '/finance/transfers/' + transfer.id + '/reject';
        document.getElementById('rejectTransferModal').style.display = 'grid';
    }
    function closeRejectTransferModal() {
        document.getElementById('rejectTransferModal').style.display = 'none';
    }

    function showLedger(userId, userName) {
        document.getElementById('ledgerModalTitle').textContent = 'Cash in Hand Ledger · ' + userName;
        document.getElementById('ledgerBody').innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--muted); padding: 20px;">Loading ledger...</td></tr>';
        document.getElementById('ledgerModal').style.display = 'grid';

        fetch('/expenses/ledger/' + userId, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            var tbody = document.getElementById('ledgerBody');
            if (!data.ledger || !data.ledger.entries || data.ledger.entries.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--muted); padding: 20px;">No cash ledger transactions recorded yet. Balance: AED ' + (data.ledger ? data.ledger.current_balance : 0).toLocaleString() + '</td></tr>';
                return;
            }
            var html = '';
            data.ledger.entries.forEach(function (e) {
                html += '<tr>' +
                    '<td>' + e.date + '</td>' +
                    '<td><b>' + e.type + '</b></td>' +
                    '<td>' + e.ref + '</td>' +
                    '<td>' + e.description + '</td>' +
                    '<td class="num" style="color:green;">' + (e.in > 0 ? '+ ' + e.in.toFixed(2) : '&mdash;') + '</td>' +
                    '<td class="num" style="color:red;">' + (e.out > 0 ? '- ' + e.out.toFixed(2) : '&mdash;') + '</td>' +
                '</tr>';
            });
            tbody.innerHTML = html;
        })
        .catch(err => {
            document.getElementById('ledgerBody').innerHTML = '<tr><td colspan="6" style="text-align: center; color: red;">Failed to load ledger.</td></tr>';
        });
    }

    function closeLedgerModal() {
        document.getElementById('ledgerModal').style.display = 'none';
    }
</script>
@endsection
