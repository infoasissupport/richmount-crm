{{--
    "Create Quotation" popup: pick a lead, then the quotation editor opens with its details filled in.
    Needs: $availableLeads (Lead::accessibleBy($user)->quotable()->get()). Open with openCreateQuoteModal();
    a page URL with ?create=1 opens it on load (the sidebar's Create Quotation link).
--}}
<div class="modal-wrap" id="createQuoteModal" style="display: none;" onclick="if(event.target === this) closeCreateQuoteModal()">
    <div class="modal modal-md" role="dialog" aria-modal="true" aria-labelledby="createQuoteModalTitle" style="max-width: 540px;">
        <div class="modal-head">
            <div>
                <h3 id="createQuoteModalTitle">Create Quotation</h3>
                <p>Choose the lead &mdash; the quotation editor opens with its details filled in.</p>
            </div>
            <button class="icon-btn" type="button" onclick="closeCreateQuoteModal()" aria-label="Close">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>
        <form method="GET" action="{{ route('quotations.builder') }}">
            <div class="modal-body" style="padding-top: 14px;">
                @if(isset($availableLeads) && $availableLeads->isNotEmpty())
                    <label class="field" style="margin-bottom: 6px;">
                        <span class="field-label">Lead</span>
                        <span class="select-wrap">
                            <select name="lead" id="quoteLeadSelect" required>
                                @foreach($availableLeads as $quotableLead)
                                    <option value="{{ $quotableLead->id }}">
                                        {{ $quotableLead->lead_number }} &middot; {{ $quotableLead->company_name }} &mdash; {{ $quotableLead->subject }}
                                    </option>
                                @endforeach
                            </select>
                        </span>
                    </label>
                @else
                    <div style="padding: 16px; background: #FFF8E6; border: 1px solid #FFE0A3; border-radius: 8px; font-size: 13px; color: #8A5B00;">
                        No active leads found to quote. Please <a href="{{ route('leads.index', ['new' => 1]) }}" style="color: var(--primary); font-weight: 600; text-decoration: underline;">create a lead first</a>.
                    </div>
                @endif
            </div>

            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeCreateQuoteModal()">Cancel</button>
                @if(isset($availableLeads) && $availableLeads->isNotEmpty())
                    <button class="btn btn-primary" type="submit">
                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-plus"/></svg>
                        Open Quotation Editor
                    </button>
                @else
                    <a class="btn btn-primary" href="{{ route('leads.index', ['new' => 1]) }}">
                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-plus"/></svg>
                        Create New Lead
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openCreateQuoteModal() {
        var modal = document.getElementById('createQuoteModal');
        if (modal) modal.style.display = 'grid';
    }
    function closeCreateQuoteModal() {
        var modal = document.getElementById('createQuoteModal');
        if (modal) modal.style.display = 'none';
    }
    document.addEventListener('keydown', function (e) {
        var modal = document.getElementById('createQuoteModal');
        if (e.key === 'Escape' && modal && modal.style.display !== 'none') closeCreateQuoteModal();
    });
    document.addEventListener('DOMContentLoaded', function () {
        if (new URLSearchParams(window.location.search).get('create') === '1') openCreateQuoteModal();
    });
</script>
@endpush
