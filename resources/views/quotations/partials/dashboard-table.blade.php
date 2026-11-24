<div class="table-wrap">
    <table class="table" id="recentQuotesTable">
        <thead>
            <tr>
                <th class="dt-no">Sl No</th>
                <th>Quote No</th>
                <th>Client</th>
                <th>Project</th>
                <th>Engineer</th>
                <th class="num">Total (AED)</th>
                <th>Status</th>
                <th>Date</th>
                <th class="ta-c">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($quotations ?? [] as $q)
                @php
                    $qLead = $q->lead;
                    $client = $qLead?->company_name ?: '—';
                    $engineerName = $qLead?->assignedEngineer?->name ?? $q->preparedBy?->name;
                    $qTone = match ($q->status) {
                        'approved' => 'blue',
                        'awarded' => 'primary',
                        default => 'gray',
                    };
                    $qLabel = ucfirst($q->status);
                @endphp
                <tr class="quote-row">
                    <td class="dt-no" data-label="Sl No">{{ $quotations->firstItem() + $loop->index }}</td>
                    <td data-label="Quote No">
                        <a href="{{ route('quotations.builder', ['id' => $q->id, 'preview' => 1]) }}" style="font-weight: 600; color: var(--primary);">
                            {{ $q->quote_number }}
                        </a>
                    </td>
                    <td class="strong" data-label="Client" title="{{ $client }}">
                        <span class="cell-who">
                            <span class="avatar sm av-{{ \App\Support\Avatar::tone($client) }}" aria-hidden="true">{{ \App\Support\Avatar::initials($client) }}</span>
                            <span class="cell-text">
                                {{ $client }}
                                @if($qLead?->contact_person)<small>Attn: {{ $qLead->contact_person }}</small>@endif
                            </span>
                        </span>
                    </td>
                    <td data-label="Project" title="{{ $qLead?->subject }}">
                        <span class="cell-text">
                            {{ $qLead?->subject ?: '—' }}
                            @if($qLead)<small>{{ $qLead->departmentLabel() }}</small>@endif
                        </span>
                    </td>
                    <td data-label="Engineer">
                        @if($engineerName)
                            <span class="cell-who">
                                <span class="avatar sm av-{{ \App\Support\Avatar::tone($engineerName) }}" aria-hidden="true">{{ \App\Support\Avatar::initials($engineerName) }}</span>
                                <span class="cell-text">
                                    {{ $engineerName }}
                                    <small>Project Engineer</small>
                                </span>
                            </span>
                        @else
                            <span style="color: var(--muted); font-size: 11px;">Unassigned</span>
                        @endif
                    </td>
                    <td class="num" data-label="Total (AED)"><b>{{ number_format($q->grand_total, 2) }}</b></td>
                    <td data-label="Status">
                        <span class="pill pill-{{ $qTone }}"><i></i>{{ $qLabel }}</span>
                    </td>
                    <td data-label="Date">
                        <span class="cell-date">
                            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-calendar"/></svg>
                            {{ $q->quote_date ? $q->quote_date->format('d M Y') : '—' }}
                        </span>
                    </td>
                    <td class="ta-c" data-label="Action">
                        <span class="row-actions">
                            <a class="act view" href="{{ route('quotations.builder', ['id' => $q->id, 'preview' => 1]) }}" title="View Quotation" aria-label="View {{ $q->quote_number }}">
                                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-eye"/></svg>
                            </a>
                            <a class="act" href="{{ route('quotations.pdf', $q) }}" title="Download PDF" aria-label="Download PDF for {{ $q->quote_number }}">
                                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-download"/></svg>
                            </a>
                            @if($q->status === 'awarded')
                                @if($q->project)
                                    <a class="act" href="{{ route('projects.show', $q->project) }}" title="Open Project" aria-label="Open Project for {{ $q->quote_number }}">
                                        <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-briefcase"/></svg>
                                    </a>
                                @elseif(auth()->user()->can('projects.create'))
                                    <form method="POST" action="{{ route('projects.create-from-quote', $q) }}" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="act" title="Create Project" aria-label="Create Project from {{ $q->quote_number }}" style="border: none; background: none; cursor: pointer; color: var(--primary);">
                                            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-briefcase"/></svg>
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="empty">
                        {{ $emptyText ?? 'No approved or awarded quotations found.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
