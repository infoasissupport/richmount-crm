{{--
    New / Edit Lead modal, shared by Lead Management and the dashboard.
    Needs: $branches, $engineers, $clientNames, $nextLeadNumber.
    Optional: $returnTo — URL to come back to after saving (defaults to the controller's redirect).
    Open with openNewLeadModal() or openEditLeadModal(lead).
--}}
<div class="modal-wrap" id="leadModal" style="display: none;" onclick="if(event.target === this) closeLeadModal()">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="leadModalTitle">
        <div class="modal-head">
            <div>
                <h3 id="leadModalTitle">New Lead</h3>
                <p id="leadModalSub">The lead appears in Lead Management, the dashboard KPIs and the pipeline.</p>
            </div>
            <button class="icon-btn" type="button" onclick="closeLeadModal()" aria-label="Close">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>
        <form id="leadForm" method="POST" action="{{ route('leads.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="leadMethod" value="POST">
            @if(!empty($returnTo))
                <input type="hidden" name="_return" value="{{ $returnTo }}">
            @endif

            <div class="modal-body">
                <div class="form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px;">
                    {{-- Row 1: Lead Number & Lead Date --}}
                    <label class="field">
                        <span class="field-label">Lead Number</span>
                        <input name="lead_number_preview" id="leadNumberInput" value="{{ $nextLeadNumber ?? 'LD-' . date('Y') . '-1001' }}" readonly style="background: #F8F9FA;">
                        <small class="field-hint" id="leadNumberHint" style="font-size: 11.5px; color: var(--muted); margin-top: 3px;">Assigned automatically</small>
                    </label>

                    <label class="field">
                        <span class="field-label">Lead Date</span>
                        <input name="lead_date" id="leadDateInput" type="date" value="{{ date('Y-m-d') }}" required>
                    </label>

                    {{-- Row 2: Client Name with Add Client --}}
                    <div class="field" style="grid-column: span 2;">
                        <label class="field-label" for="leadCompanyInput">Client Name <span style="color: var(--primary);">*</span></label>
                        <div class="input-action">
                            <input name="company_name" id="leadCompanyInput" required maxlength="120" placeholder="Client Name" list="clientList" autocomplete="off">
                            <button type="button" class="btn btn-soft" id="addClientBtn" onclick="openAddClientPrompt()">
                                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-plus"/></svg>
                                Add Client
                            </button>
                        </div>
                        <datalist id="clientList">
                            @foreach($clientNames ?? [] as $clName)
                                <option value="{{ $clName }}">
                            @endforeach
                        </datalist>
                    </div>

                    {{-- Row 3: RFQ / PR Number & Project Subject --}}
                    <label class="field">
                        <span class="field-label">RFQ / PR Number <span style="color: var(--primary);">*</span></span>
                        <input name="rfq_number" id="leadRfqInput" maxlength="50" placeholder="RFQ / PR Number">
                    </label>

                    <label class="field">
                        <span class="field-label">Project Subject</span>
                        <input name="subject" id="leadSubjectInput" maxlength="150" placeholder="Project Subject">
                    </label>

                    {{-- Row 4: Contact Person & Phone --}}
                    <label class="field">
                        <span class="field-label">Contact Person</span>
                        <input name="contact_person" id="leadContactInput" maxlength="100" placeholder="Full Name">
                    </label>

                    <label class="field">
                        <span class="field-label">Phone</span>
                        <input name="phone" id="leadPhoneInput" maxlength="30" placeholder="Phone Number" inputmode="tel">
                    </label>

                    {{-- Row 5: Email & Budget (AED) --}}
                    <label class="field">
                        <span class="field-label">Email</span>
                        <input name="email" id="leadEmailInput" type="email" maxlength="100" placeholder="Email">
                    </label>

                    <label class="field">
                        <span class="field-label">Budget (AED)</span>
                        <input name="estimated_value" id="leadBudgetInput" type="number" step="1000" min="0" placeholder="0">
                    </label>

                    {{-- Row 6: Source & Branch (Branch is hidden when editing, so Assigned Engineer moves up beside Source) --}}
                    <label class="field">
                        <span class="field-label">Source <span style="color: var(--primary);">*</span></span>
                        <span class="select-wrap">
                            <select name="source" id="leadSourceSelect" required>
                                <option value="Website">Website</option>
                                <option value="Referral">Referral</option>
                                <option value="Exhibition">Exhibition</option>
                                <option value="Email">Email</option>
                                <option value="Phone">Phone</option>
                                <option value="Social Media">Social Media</option>
                            </select>
                        </span>
                    </label>

                    <label class="field" id="leadBranchField">
                        <span class="field-label">Branch <span style="color: var(--primary);">*</span></span>
                        <span class="select-wrap">
                            <select name="branch_id" id="leadBranchSelect" required onchange="filterEngineersByBranch(this.value)">
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->code }})</option>
                                @endforeach
                            </select>
                        </span>
                    </label>

                    {{-- Row 7: Assigned Engineer --}}
                    <label class="field">
                        <span class="field-label">Assigned Engineer <span style="color: var(--primary);">*</span></span>
                        <span class="select-wrap">
                            <select name="assigned_engineer_id" id="leadEngineerSelect" required>
                                <option value="">Select Engineer</option>
                                @foreach($engineers as $eng)
                                    <option value="{{ $eng->id }}" data-branch="{{ $eng->branch_id }}">{{ $eng->name }} ({{ $eng->branch?->short_name }})</option>
                                @endforeach
                            </select>
                        </span>
                    </label>

                    {{-- Row 8: Notes --}}
                    <label class="field" style="grid-column: span 2;">
                        <span class="field-label">Notes</span>
                        <textarea name="notes" id="leadNotesInput" rows="2" maxlength="1000" placeholder="Note"></textarea>
                    </label>

                    {{-- Row 9: Attachments --}}
                    <label class="field" style="grid-column: span 2;">
                        <span class="field-label">Attachments</span>
                        <input type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.dwg,.xlsx,.docx,.doc">
                    </label>
                </div>
            </div>

            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeLeadModal()">Cancel</button>
                <button class="btn btn-ghost" type="submit" name="save_as_draft" value="1" id="saveDraftBtn">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-bookmark"/></svg>
                    Save Draft
                </button>
                <button class="btn btn-primary" type="submit" id="saveLeadBtn">
                    <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-plus"/></svg>
                    Create Lead
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function showLeadModal() {
        var m = document.getElementById('leadModal');
        m.style.display = 'flex';
        m.classList.add('open');
    }

    function openNewLeadModal() {
        document.getElementById('leadModalTitle').textContent = 'New Lead';
        document.getElementById('leadModalSub').textContent = 'The lead appears in Lead Management, the dashboard KPIs and the pipeline.';
        document.getElementById('leadMethod').value = 'POST';
        document.getElementById('leadForm').action = "{{ route('leads.store') }}";

        document.getElementById('leadNumberInput').value = "{{ $nextLeadNumber ?? 'LD-' . date('Y') . '-1001' }}";
        document.getElementById('leadNumberHint').hidden = false;
        document.getElementById('leadDateInput').value = "{{ date('Y-m-d') }}";
        document.getElementById('leadCompanyInput').value = '';
        document.getElementById('leadSubjectInput').value = '';
        document.getElementById('leadContactInput').value = '';
        document.getElementById('leadPhoneInput').value = '';
        document.getElementById('leadEmailInput').value = '';
        document.getElementById('leadRfqInput').value = '';
        document.getElementById('leadBudgetInput').value = '';
        document.getElementById('leadNotesInput').value = '';
        document.getElementById('leadBranchField').hidden = false;
        filterEngineersByBranch('');
        document.getElementById('saveLeadBtn').innerHTML = '<svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-plus"/></svg> Create Lead';
        document.getElementById('saveDraftBtn').style.display = 'inline-flex';
        showLeadModal();
    }

    function openEditLeadModal(lead) {
        var stage = lead.stage ? lead.stage.charAt(0).toUpperCase() + lead.stage.slice(1) : 'Active';
        document.getElementById('leadModalTitle').textContent = 'Edit Lead';
        document.getElementById('leadModalSub').textContent = (lead.lead_number ? lead.lead_number + ' · ' : '') + stage;
        document.getElementById('leadMethod').value = 'PUT';
        document.getElementById('leadForm').action = "{{ route('leads.update', '__ID__') }}".replace('__ID__', lead.id);

        document.getElementById('leadNumberInput').value = lead.lead_number || '';
        document.getElementById('leadNumberHint').hidden = true;
        document.getElementById('leadCompanyInput').value = lead.company_name || '';
        document.getElementById('leadSubjectInput').value = lead.subject || '';
        document.getElementById('leadContactInput').value = lead.contact_person || '';
        document.getElementById('leadPhoneInput').value = lead.phone || '';
        document.getElementById('leadEmailInput').value = lead.email || '';
        document.getElementById('leadRfqInput').value = lead.rfq_number || '';
        // Branch stays on the lead (still submitted) but isn't edited here, as in the prototype
        document.getElementById('leadBranchSelect').value = lead.branch_id || '';
        document.getElementById('leadBranchField').hidden = true;
        filterEngineersByBranch(String(lead.branch_id || ''));
        document.getElementById('leadEngineerSelect').value = lead.assigned_engineer_id || '';
        document.getElementById('leadSourceSelect').value = lead.source || 'Website';
        document.getElementById('leadBudgetInput').value = lead.estimated_value ? Math.round(lead.estimated_value) : '';
        document.getElementById('leadDateInput').value = lead.lead_date ? String(lead.lead_date).substring(0, 10) : '{{ date('Y-m-d') }}';
        document.getElementById('leadNotesInput').value = lead.notes || '';
        document.getElementById('saveLeadBtn').innerHTML = '<svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg> Save Changes';
        document.getElementById('saveDraftBtn').style.display = 'none';
        showLeadModal();
    }

    function openAddClientPrompt() {
        var name = prompt('Enter new client name:');
        if (name && name.trim()) {
            name = name.trim();
            var clientInput = document.getElementById('leadCompanyInput');
            clientInput.value = name;
            var datalist = document.getElementById('clientList');
            if (datalist) {
                var opt = document.createElement('option');
                opt.value = name;
                datalist.appendChild(opt);
            }
        }
    }

    function closeLeadModal() {
        var m = document.getElementById('leadModal');
        m.classList.remove('open');
        m.style.display = 'none';
    }

    function filterEngineersByBranch(branchId) {
        var options = document.querySelectorAll('#leadEngineerSelect option');
        options.forEach(function (opt) {
            if (!opt.value) return;
            var optBranch = opt.getAttribute('data-branch');
            opt.style.display = (!branchId || optBranch === branchId) ? 'block' : 'none';
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && document.getElementById('leadModal').style.display !== 'none') closeLeadModal();
    });
</script>
@endpush
