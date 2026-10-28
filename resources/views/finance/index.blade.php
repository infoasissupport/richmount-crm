@extends('layouts.app')

@section('title', 'Finance & Approvals')
@section('page_id', 'finance')

@section('breadcrumbs')
    <svg class="ic sep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
    <span class="crumb current">Finance & Approvals</span>
@endsection

@section('content')
<section class="page-head" id="pageHead">
    <div>
        <h1>Finance & Approval Feed</h1>
        <p class="sub">Central review hub for engineer petty cash expenses, internal transfers, and billing overview.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-ghost" href="{{ route('expenses.index') }}">
            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-receipt"/></svg>
            All Expenses
        </a>
        <a class="btn btn-ghost" href="{{ route('reports.index') }}">
            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-bar-chart"/></svg>
            Financial Reports
        </a>
        @can('invoices.create')
        <a class="btn btn-primary" href="{{ route('projects.index') }}" title="Raise invoice from active project">
            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-invoice"/></svg>
            Create Invoice
        </a>
        @endcan
    </div>
</section>

<div class="dash-scroll">
    {{-- Financial KPIs --}}
    <section class="kpi-grid four" id="kpis" aria-label="Key figures">
        <div class="kpi static">
            <span class="kpi-top">
                <span class="kpi-icon" style="background: rgba(234, 88, 12, 0.12); color: #ea580c;">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-clock"/></svg>
                </span>
            </span>
            <span class="kpi-value">{{ $kpis['pending_approvals'] }}</span>
            <span class="kpi-label">Pending Approvals</span>
            <span class="kpi-note">Expenses & Transfers awaiting action</span>
        </div>

        <div class="kpi static">
            <span class="kpi-top">
                <span class="kpi-icon">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-wallet"/></svg>
                </span>
            </span>
            <span class="kpi-value">AED {{ number_format($kpis['total_expenses'], 2) }}</span>
            <span class="kpi-label">Total Cleared Expenses</span>
            <span class="kpi-note">Approved petty cash & card costs</span>
        </div>

        <div class="kpi static">
            <span class="kpi-top">
                <span class="kpi-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-text"/></svg>
                </span>
            </span>
            <span class="kpi-value">AED {{ number_format($kpis['total_invoiced'], 2) }}</span>
            <span class="kpi-label">Total Invoiced</span>
            <span class="kpi-note">Sent & paid client invoices</span>
        </div>

        <div class="kpi static">
            <span class="kpi-top">
                <span class="kpi-icon" style="background: rgba(59, 130, 246, 0.12); color: #3b82f6;">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-briefcase"/></svg>
                </span>
            </span>
            <span class="kpi-value">{{ $kpis['awaiting_activation'] }}</span>
            <span class="kpi-label">Awaiting PO Activation</span>
            <span class="kpi-note">Won quotes pending PO recording</span>
        </div>
    </section>

    {{-- Pending Expenses Feed --}}
    <section class="card" id="pendingExpensesTable">
        <header class="card-head">
            <h2>
                <span class="head-ic">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-receipt"/></svg>
                </span>
                Pending Expense Approvals
                @if($pendingExpenses->count() > 0)
                    <span class="pill pill-warning" style="margin-left: 8px;">{{ $pendingExpenses->count() }} Pending</span>
                @endif
            </h2>
        </header>

        <div class="table-wrap">
            <table class="table" style="font-size: 11.5px;">
                <thead>
                    <tr>
                        <th>Expense No</th>
                        <th>Date</th>
                        <th>Submitted By</th>
                        <th>Branch</th>
                        <th>Project</th>
                        <th>Vendor / Description</th>
                        <th>Type</th>
                        <th class="num">Amount (AED)</th>
                        <th class="ta-c">Receipt</th>
                        <th class="ta-c">Decision</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingExpenses as $exp)
                        <tr>
                            <td class="strong">{{ $exp->expense_number }}</td>
                            <td>{{ $exp->date->format('d M Y') }}</td>
                            <td>
                                <b>{{ $exp->user->name }}</b>
                                <div style="font-size: 10px; color: var(--muted);">Balance: AED {{ number_format($exp->user->cash_balance, 2) }}</div>
                            </td>
                            <td>{{ $exp->branch->name }}</td>
                            <td>
                                @if($exp->project)
                                    <a href="{{ route('projects.show', $exp->project) }}" style="color: var(--primary); text-decoration: none; font-weight: 500;">
                                        {{ $exp->project->project_number }}
                                    </a>
                                @else
                                    <span style="color: var(--muted);">General / Overhead</span>
                                @endif
                            </td>
                            <td>
                                <div><b>{{ $exp->vendor_name }}</b></div>
                                <div style="font-size: 10.5px; color: var(--muted); max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $exp->description }}
                                </div>
                            </td>
                            <td>
                                <span class="pill {{ $exp->type === 'cash' ? 'pill-info' : 'pill-purple' }}">
                                    {{ ucfirst($exp->type) }}
                                </span>
                            </td>
                            <td class="num">
                                <b>{{ number_format($exp->grand_total, 2) }}</b>
                                @if($exp->vat_amount > 0)
                                    <div style="font-size: 9.5px; color: var(--muted);">VAT: {{ number_format($exp->vat_amount, 2) }}</div>
                                @endif
                            </td>
                            <td class="ta-c">
                                @if($exp->receipt_file)
                                    <a href="{{ asset('storage/' . $exp->receipt_file) }}" target="_blank" class="btn btn-ghost btn-sm" title="View Receipt">
                                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-eye"/></svg>
                                    </a>
                                @else
                                    <span style="color: var(--muted); font-size: 10px;">None</span>
                                @endif
                            </td>
                            <td class="ta-c">
                                @can('expenses.approve')
                                <div style="display: flex; gap: 6px; justify-content: center;">
                                    <form action="{{ route('finance.expenses.approve', $exp) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button class="btn btn-success btn-sm" type="submit">
                                            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                                            Approve
                                        </button>
                                    </form>
                                    <button class="btn btn-danger btn-sm" type="button" onclick="openRejectExpenseModal({{ json_encode($exp) }})">
                                        Reject
                                    </button>
                                </div>
                                @else
                                <span style="color: var(--muted); font-size: 11px;">Awaiting Accountant</span>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="ta-c" style="padding: 32px; color: var(--muted);">
                                <svg class="ic" style="width: 28px; height: 28px; stroke: #10b981; margin-bottom: 6px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-check"/></svg>
                                <div>No pending expenses! All submitted expenses have been processed.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Pending Internal Transfers Feed --}}
    <section class="card" id="pendingTransfersTable" style="margin-top: 20px;">
        <header class="card-head">
            <h2>
                <span class="head-ic">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-refresh"/></svg>
                </span>
                Pending Internal Transfers
                @if($pendingTransfers->count() > 0)
                    <span class="pill pill-warning" style="margin-left: 8px;">{{ $pendingTransfers->count() }} Pending</span>
                @endif
            </h2>
        </header>

        <div class="table-wrap">
            <table class="table" style="font-size: 11.5px;">
                <thead>
                    <tr>
                        <th>Transfer No</th>
                        <th>Date</th>
                        <th>Disbursed From</th>
                        <th>Recipient Engineer</th>
                        <th>Branch</th>
                        <th>Payment Method</th>
                        <th class="num">Amount (AED)</th>
                        <th>Notes</th>
                        <th class="ta-c">Decision</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingTransfers as $tr)
                        <tr>
                            <td class="strong">{{ $tr->transfer_number }}</td>
                            <td>{{ $tr->date->format('d M Y') }}</td>
                            <td>{{ $tr->fromUser?->name ?? 'Main Float / Company' }}</td>
                            <td>
                                <b>{{ $tr->toUser->name }}</b>
                                <div style="font-size: 10px; color: var(--muted);">Current Balance: AED {{ number_format($tr->toUser->cash_balance, 2) }}</div>
                            </td>
                            <td>{{ $tr->branch->name }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $tr->payment_method)) }}</td>
                            <td class="num"><b>{{ number_format($tr->amount, 2) }}</b></td>
                            <td style="color: var(--muted); font-size: 11px;">{{ $tr->notes ?: '—' }}</td>
                            <td class="ta-c">
                                @can('transfers.approve')
                                <div style="display: flex; gap: 6px; justify-content: center;">
                                    <form action="{{ route('finance.transfers.approve', $tr) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button class="btn btn-success btn-sm" type="submit">
                                            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                                            Approve
                                        </button>
                                    </form>
                                    <button class="btn btn-danger btn-sm" type="button" onclick="openRejectTransferModal({{ json_encode($tr) }})">
                                        Reject
                                    </button>
                                    @can('transfers.edit')
                                    <button class="btn btn-ghost btn-sm" type="button" onclick="openEditTransferModal({{ json_encode($tr) }})" title="Accountant Edit">
                                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-edit"/></svg>
                                    </button>
                                    @endcan
                                </div>
                                @else
                                <span style="color: var(--muted); font-size: 11px;">Awaiting Accountant</span>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="ta-c" style="padding: 32px; color: var(--muted);">
                                <svg class="ic" style="width: 28px; height: 28px; stroke: #10b981; margin-bottom: 6px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-check"/></svg>
                                <div>No pending transfer requests. Float distributions are up to date!</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Tax Invoices & Receivables Overview --}}
    <section class="card" id="invoicesOverviewTable" style="margin-top: 20px;">
        <header class="card-head">
            <h2>
                <span class="head-ic">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-text"/></svg>
                </span>
                Recent Tax Invoices & Receivables
            </h2>
        </header>

        <div class="table-wrap">
            <table class="table" style="font-size: 11.5px;">
                <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Date</th>
                        <th>Project</th>
                        <th>Client</th>
                        <th>Branch</th>
                        <th class="num">Grand Total (AED)</th>
                        <th class="num">Paid (AED)</th>
                        <th class="num">Balance (AED)</th>
                        <th>Status</th>
                        <th class="ta-c">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                        <tr>
                            <td class="strong">{{ $inv->invoice_number }}</td>
                            <td>{{ $inv->invoice_date->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('projects.show', $inv->project) }}" style="color: var(--primary); text-decoration: none; font-weight: 500;">
                                    {{ $inv->project->project_number }}
                                </a>
                            </td>
                            <td><b>{{ $inv->project->client_name }}</b></td>
                            <td>{{ $inv->branch->name }}</td>
                            <td class="num"><b>{{ number_format($inv->grand_total, 2) }}</b></td>
                            <td class="num" style="color: #10b981;">{{ number_format($inv->amount_paid, 2) }}</td>
                            <td class="num" style="color: {{ $inv->balance_due > 0 ? '#ef4444' : 'var(--muted)' }};">
                                <b>{{ number_format($inv->balance_due, 2) }}</b>
                            </td>
                            <td><span class="pill pill-{{ $inv->statusTone() }}">{{ ucfirst($inv->status) }}</span></td>
                            <td class="ta-c">
                                <a href="{{ route('invoices.pdf', $inv) }}" target="_blank" class="btn btn-ghost btn-sm" title="Download Official Tax Invoice PDF">
                                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-download"/></svg>
                                    PDF
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="ta-c" style="padding: 24px; color: var(--muted);">No invoices generated yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

{{-- Reject Expense Modal --}}
<div class="modal-backdrop" id="rejectExpenseModal" style="display:none;" onclick="if(event.target === this) closeRejectExpenseModal()">
    <div class="modal modal-md">
        <header class="modal-head">
            <h2>Reject Expense</h2>
            <button class="btn btn-ghost icon-only" type="button" onclick="closeRejectExpenseModal()">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </header>

        <form id="rejectExpenseForm" method="POST" action="">
            @csrf
            <div class="modal-body">
                <p style="font-size: 13px; color: var(--text); margin-bottom: 12px;">
                    Please state the reason for rejecting expense <strong id="rejectExpenseNumber"></strong>. This reason will be logged and visible to the engineer.
                </p>

                <div class="form-row">
                    <label class="form-label required" for="expRejectReason">Rejection Reason</label>
                    <textarea class="textarea" id="expRejectReason" name="reason" rows="3" required placeholder="e.g. Receipt unreadable, missing tax invoice number, or wrong project code."></textarea>
                </div>
            </div>

            <footer class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeRejectExpenseModal()">Cancel</button>
                <button class="btn btn-danger" type="submit">Confirm Rejection</button>
            </footer>
        </form>
    </div>
</div>

{{-- Reject Transfer Modal --}}
<div class="modal-backdrop" id="rejectTransferModal" style="display:none;" onclick="if(event.target === this) closeRejectTransferModal()">
    <div class="modal modal-md">
        <header class="modal-head">
            <h2>Reject Transfer</h2>
            <button class="btn btn-ghost icon-only" type="button" onclick="closeRejectTransferModal()">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </header>

        <form id="rejectTransferForm" method="POST" action="">
            @csrf
            <div class="modal-body">
                <p style="font-size: 13px; color: var(--text); margin-bottom: 12px;">
                    Please state the reason for rejecting transfer request <strong id="rejectTransferNumber"></strong>.
                </p>

                <div class="form-row">
                    <label class="form-label required" for="trRejectReason">Rejection Reason</label>
                    <textarea class="textarea" id="trRejectReason" name="reason" rows="3" required placeholder="e.g. Insufficient float balance or unauthorized request."></textarea>
                </div>
            </div>

            <footer class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeRejectTransferModal()">Cancel</button>
                <button class="btn btn-danger" type="submit">Confirm Rejection</button>
            </footer>
        </form>
    </div>
</div>

{{-- Edit Transfer Modal (Accountant Privilege) --}}
<div class="modal-backdrop" id="editTransferModal" style="display:none;" onclick="if(event.target === this) closeEditTransferModal()">
    <div class="modal modal-md">
        <header class="modal-head">
            <h2>Edit Transfer (Accountant Adjust)</h2>
            <button class="btn btn-ghost icon-only" type="button" onclick="closeEditTransferModal()">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </header>

        <form id="editTransferForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <p class="lock-note" style="margin-bottom: 14px; font-size: 11.5px; color: var(--muted);">
                    As Accountant, you may adjust the approved disbursement amount, payment method, or notes before finalizing.
                </p>

                <div class="form-row">
                    <label class="form-label required" for="editTransferAmount">Amount (AED)</label>
                    <input class="input" type="number" step="0.01" min="0.01" id="editTransferAmount" name="amount" required>
                </div>

                <div class="form-row">
                    <label class="form-label required" for="editTransferMethod">Payment Method</label>
                    <select class="input" id="editTransferMethod" name="payment_method" required>
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>

                <div class="form-row">
                    <label class="form-label" for="editTransferNotes">Notes</label>
                    <textarea class="textarea" id="editTransferNotes" name="notes" rows="2"></textarea>
                </div>
            </div>

            <footer class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeEditTransferModal()">Cancel</button>
                <button class="btn btn-primary" type="submit">Save Changes</button>
            </footer>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openRejectExpenseModal(expense) {
        document.getElementById('rejectExpenseNumber').innerText = expense.expense_number;
        document.getElementById('rejectExpenseForm').action = '/finance/expenses/' + expense.id + '/reject';
        document.getElementById('expRejectReason').value = '';
        document.getElementById('rejectExpenseModal').style.display = 'flex';
    }

    function closeRejectExpenseModal() {
        document.getElementById('rejectExpenseModal').style.display = 'none';
    }

    function openRejectTransferModal(transfer) {
        document.getElementById('rejectTransferNumber').innerText = transfer.transfer_number;
        document.getElementById('rejectTransferForm').action = '/finance/transfers/' + transfer.id + '/reject';
        document.getElementById('trRejectReason').value = '';
        document.getElementById('rejectTransferModal').style.display = 'flex';
    }

    function closeRejectTransferModal() {
        document.getElementById('rejectTransferModal').style.display = 'none';
    }

    function openEditTransferModal(transfer) {
        document.getElementById('editTransferForm').action = '/transfers/' + transfer.id;
        document.getElementById('editTransferAmount').value = transfer.amount;
        document.getElementById('editTransferMethod').value = transfer.payment_method;
        document.getElementById('editTransferNotes').value = transfer.notes || '';
        document.getElementById('editTransferModal').style.display = 'flex';
    }

    function closeEditTransferModal() {
        document.getElementById('editTransferModal').style.display = 'none';
    }
</script>
@endpush
@endsection
