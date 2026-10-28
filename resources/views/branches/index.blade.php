@extends('layouts.app')

@section('title', 'Branches')
@section('page_id', 'branches')

@section('breadcrumbs')
    <svg class="ic sep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
    <span class="crumb current">Branches</span>
@endsection

@section('content')
<section class="page-head" id="pageHead">
    <div>
        <h1>Branches</h1>
        <p class="sub">Every record carries its branch. Each branch has its own quotation format.</p>
    </div>
    <div class="page-actions">
        @can('branches.edit')
        <button class="btn btn-primary" type="button" onclick="openCreateBranchModal()">
            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-plus"/></svg>
            New Branch
        </button>
        @endcan
    </div>
</section>

<div class="dash-scroll">
    {{-- Branches Card --}}
    <section class="card" id="branchesTable">
        <header class="card-head">
            <h2>
                <span class="head-ic">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-building"/></svg>
                </span>
                All Branches
            </h2>
        </header>

        <div class="table-wrap page">
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Branch Name</th>
                        <th>Country</th>
                        <th>Quote Format</th>
                        <th>TRN Number</th>
                        <th class="num">Engineers</th>
                        <th class="num">Total Users</th>
                        <th>Status</th>
                        <th class="ta-c">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($branches as $b)
                        <tr>
                            <td class="strong">{{ $b->code }}</td>
                            <td>
                                <strong>{{ $b->name }}</strong>
                                @if($b->address)<br><small style="color:#666;">{{ $b->address }}</small>@endif
                            </td>
                            <td>{{ $b->country }}</td>
                            <td>
                                @php
                                    $fmtLabel = match($b->quote_format) {
                                        'format_1' => 'Quote Format 1',
                                        'format_2' => 'Quote Format 2',
                                        'format_3' => 'Quote Format 3',
                                        default => $b->quote_format
                                    };
                                @endphp
                                <span class="pill pill-primary"><i></i>{{ $fmtLabel }}</span>
                            </td>
                            <td><code>{{ $b->trn_number ?: '—' }}</code></td>
                            <td class="num"><b>{{ $b->engineers_count }}</b></td>
                            <td class="num">{{ $b->users_count }}</td>
                            <td>
                                <span class="pill {{ $b->is_active ? 'pill-green' : 'pill-red' }}">
                                    <i></i>{{ $b->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="ta-c">
                                <span class="row-actions">
                                    @can('branches.edit')
                                    <button class="act edit" type="button" title="Edit" onclick="openEditBranchModal({{ json_encode($b) }})">
                                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-edit"/></svg>
                                    </button>

                                    @if($b->users_count == 0)
                                    <form action="{{ route('branches.destroy', $b) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete branch {{ $b->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="act delete" type="submit" title="Delete">
                                            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
                                        </button>
                                    </form>
                                    @endif
                                    @endcan
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="empty">No branches configured.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Engineers by Branch Card --}}
    <section class="card" id="engineersTable">
        <header class="card-head">
            <h2>
                <span class="head-ic">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-users"/></svg>
                </span>
                Engineers by Branch
            </h2>
        </header>

        <div class="table-wrap page">
            <table class="table">
                <thead>
                    <tr>
                        <th>Engineer</th>
                        <th>Branch</th>
                        <th>Code</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Zoho Petty Cash Account</th>
                        <th class="num">Cash in Hand</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($engineers as $eng)
                        <tr>
                            <td class="strong">
                                <span class="cell-who">
                                    <span class="avatar sm av-blue">{{ $eng->initials }}</span>
                                    <span class="cell-text">{{ $eng->name }}</span>
                                </span>
                            </td>
                            <td>{{ $eng->branch?->name ?? 'Unassigned' }}</td>
                            <td><code>{{ $eng->engineer_code ?: '—' }}</code></td>
                            <td>{{ $eng->email }}</td>
                            <td>{{ $eng->phone ?: '—' }}</td>
                            <td><code>{{ $eng->zoho_petty_cash_account_id ?: 'Petty Cash - ' . $eng->name }}</code></td>
                            <td class="num"><b>AED {{ number_format($eng->cash_balance, 2) }}</b></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty">No project engineers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

{{-- Create / Edit Branch Modal --}}
<div class="modal-wrap" id="branchModal" style="display: none;">
    <div class="modal modal-md" role="dialog" aria-modal="true" aria-labelledby="branchModalTitle">
        <div class="modal-head">
            <div>
                <h3 id="branchModalTitle">New Branch</h3>
                <p>Configure branch details and quotation template</p>
            </div>
            <button class="icon-btn" type="button" onclick="closeBranchModal()" aria-label="Close">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>
        <form id="branchForm" method="POST" action="{{ route('branches.store') }}">
            @csrf
            <input type="hidden" name="_method" id="branchMethod" value="POST">

            <div class="modal-body">
                <div class="form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px;">
                    <label class="field">
                        <span class="field-label">Branch Name *</span>
                        <input name="name" id="branchNameInput" required maxlength="100" placeholder="e.g. Dubai Branch">
                    </label>

                    <label class="field">
                        <span class="field-label">Short Name *</span>
                        <input name="short_name" id="branchShortInput" required maxlength="50" placeholder="e.g. Dubai">
                    </label>

                    <label class="field">
                        <span class="field-label">Branch Code *</span>
                        <input name="code" id="branchCodeInput" required maxlength="20" placeholder="e.g. Branch 1">
                    </label>

                    <label class="field">
                        <span class="field-label">Country *</span>
                        <input name="country" id="branchCountryInput" required maxlength="50" value="UAE">
                    </label>

                    <label class="field">
                        <span class="field-label">Quote Format Template *</span>
                        <span class="select-wrap">
                            <select name="quote_format" id="branchFormatSelect" required>
                                <option value="format_1">Format 1 (Dubai Standard)</option>
                                <option value="format_2">Format 2 (Abu Dhabi Technical)</option>
                                <option value="format_3">Format 3 (Northern Emirates)</option>
                            </select>
                        </span>
                    </label>

                    <label class="field">
                        <span class="field-label">TRN Number (VAT)</span>
                        <input name="trn_number" id="branchTrnInput" maxlength="50" placeholder="e.g. 100 234 567 800 001">
                    </label>

                    <label class="field" style="grid-column: span 2;">
                        <span class="field-label">Office Address</span>
                        <input name="address" id="branchAddressInput" maxlength="255" placeholder="Building, Suite, City">
                    </label>

                    <label class="field">
                        <span class="field-label">Contact Phone</span>
                        <input name="phone" id="branchPhoneInput" maxlength="30" placeholder="+971 4 000 0000">
                    </label>

                    <label class="field">
                        <span class="field-label">Email Address</span>
                        <input name="email" id="branchEmailInput" type="email" maxlength="100" placeholder="branch@richmount.ae">
                    </label>

                    <label class="field" style="grid-column: span 2; display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="is_active" id="branchIsActive" value="1" checked>
                        <span>Branch is Active</span>
                    </label>
                </div>
            </div>

            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeBranchModal()">Cancel</button>
                <button class="btn btn-primary" type="submit">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg>
                    Save Branch
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCreateBranchModal() {
        document.getElementById('branchModalTitle').textContent = 'New Branch';
        document.getElementById('branchMethod').value = 'POST';
        document.getElementById('branchForm').action = "{{ route('branches.store') }}";
        
        document.getElementById('branchNameInput').value = '';
        document.getElementById('branchShortInput').value = '';
        document.getElementById('branchCodeInput').value = '';
        document.getElementById('branchCountryInput').value = 'UAE';
        document.getElementById('branchFormatSelect').value = 'format_1';
        document.getElementById('branchTrnInput').value = '';
        document.getElementById('branchAddressInput').value = '';
        document.getElementById('branchPhoneInput').value = '';
        document.getElementById('branchEmailInput').value = '';
        document.getElementById('branchIsActive').checked = true;

        document.getElementById('branchModal').style.display = 'grid';
    }

    function openEditBranchModal(branch) {
        document.getElementById('branchModalTitle').textContent = 'Edit ' + branch.name;
        document.getElementById('branchMethod').value = 'PUT';
        document.getElementById('branchForm').action = "/branches/" + branch.id;

        document.getElementById('branchNameInput').value = branch.name || '';
        document.getElementById('branchShortInput').value = branch.short_name || '';
        document.getElementById('branchCodeInput').value = branch.code || '';
        document.getElementById('branchCountryInput').value = branch.country || 'UAE';
        document.getElementById('branchFormatSelect').value = branch.quote_format || 'format_1';
        document.getElementById('branchTrnInput').value = branch.trn_number || '';
        document.getElementById('branchAddressInput').value = branch.address || '';
        document.getElementById('branchPhoneInput').value = branch.phone || '';
        document.getElementById('branchEmailInput').value = branch.email || '';
        document.getElementById('branchIsActive').checked = !!branch.is_active;

        document.getElementById('branchModal').style.display = 'grid';
    }

    function closeBranchModal() {
        document.getElementById('branchModal').style.display = 'none';
    }
</script>
@endsection
