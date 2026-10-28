@extends('layouts.app')

@section('title', 'Zoho Books')
@section('page_id', 'zoho')

@section('breadcrumbs')
    <svg class="ic sep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
    <span class="crumb current">Zoho Books</span>
@endsection

@section('content')
<section class="page-head" id="pageHead">
    <div>
        <h1>Zoho Books</h1>
        <p class="sub">Two-way sync for petty cash accounts, project creation, tax invoices, and status tracking.</p>
    </div>
    <div class="page-actions">
        @can('zoho.sync')
        <form action="{{ route('zoho.sync') }}" method="POST" style="display:inline;">
            @csrf
            <button class="btn btn-primary" type="submit" id="syncNowBtn">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-refresh"/></svg>
                Sync Queue Now
            </button>
        </form>
        @endcan
    </div>
</section>

<div class="dash-scroll">
    {{-- Connection Status & Mini-stats --}}
    <section class="card" id="zohoStatus" style="padding: 16px;">
        <div class="zoho-status">
            <span class="zs-dot" style="{{ $status['connected'] ? 'background: #10b981;' : 'background: #ef4444;' }}"></span>
            <div>
                <strong>Connected to Zoho Books</strong>
                <small>
                    {{ $status['organization_name'] }} &bull; 
                    {{ $status['is_mock'] ? 'Demonstration integration layer (mock enabled)' : 'Production OAuth2 connected' }} &bull; 
                    Last sync: {{ $status['last_sync'] ? $status['last_sync']->format('d M Y, h:i A') : 'Recently synchronized' }}
                </small>
            </div>
        </div>

        <div class="mini-stats" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 0;">
            <div style="background: var(--bg-subtle); padding: 12px; border-radius: 8px; border: 1px solid var(--border);">
                <small style="color: var(--muted); font-size: 11px; text-transform: uppercase;">Synced</small>
                <strong style="display: block; font-size: 20px; font-weight: 700; color: #10b981;">{{ $status['synced_count'] }}</strong>
            </div>

            <div style="background: var(--bg-subtle); padding: 12px; border-radius: 8px; border: 1px solid var(--border);">
                <small style="color: var(--muted); font-size: 11px; text-transform: uppercase;">Pending Sync</small>
                <strong style="display: block; font-size: 20px; font-weight: 700; color: #f59e0b;">{{ $status['pending_count'] }}</strong>
            </div>

            <div class="{{ $status['failed_count'] > 0 ? 'bad' : '' }}" style="background: var(--bg-subtle); padding: 12px; border-radius: 8px; border: 1px solid var(--border);">
                <small style="color: var(--muted); font-size: 11px; text-transform: uppercase;">Failed</small>
                <strong style="display: block; font-size: 20px; font-weight: 700; color: {{ $status['failed_count'] > 0 ? '#ef4444' : 'var(--text)' }};">
                    {{ $status['failed_count'] }}
                </strong>
            </div>

            <div style="background: var(--bg-subtle); padding: 12px; border-radius: 8px; border: 1px solid var(--border);">
                <small style="color: var(--muted); font-size: 11px; text-transform: uppercase;">Petty Cash Accounts</small>
                <strong style="display: block; font-size: 20px; font-weight: 700; color: var(--primary);">{{ $status['petty_cash_accounts_count'] }}</strong>
            </div>
        </div>
    </section>

    {{-- Sync Logs Table --}}
    <section class="card" id="zohoLog" style="margin-top: 20px;">
        <header class="card-head">
            <h2>
                <span class="head-ic">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-book"/></svg>
                </span>
                Sync Log
            </h2>

            <div class="card-actions">
                <form method="GET" action="{{ route('zoho.index') }}" style="display: flex; gap: 8px;">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <input class="input sm" type="search" name="search" placeholder="Search reference, entity..." value="{{ request('search') }}" style="width: 220px;">
                </form>
            </div>
        </header>

        {{-- Tabs --}}
        <div class="table-tabs" style="display: flex; gap: 8px; border-bottom: 1px solid var(--border); padding: 0 16px 8px 16px; margin-bottom: 12px;">
            @php
                $syncTabs = [
                    'all' => 'All',
                    'failed' => 'Failed (' . $status['failed_count'] . ')',
                    'queued' => 'Pending Sync (' . $status['pending_count'] . ')',
                    'synced' => 'Synced (' . $status['synced_count'] . ')',
                ];
            @endphp
            @foreach($syncTabs as $tKey => $tLabel)
                <a href="{{ route('zoho.index', ['tab' => $tKey, 'search' => request('search')]) }}"
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
                        <th>Time</th>
                        <th>Entity</th>
                        <th>Reference</th>
                        <th>Action</th>
                        <th>Status</th>
                        <th>Message</th>
                        <th class="ta-c">Retry</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d M Y h:i A') }}</td>
                            <td>
                                <span class="pill pill-info" style="font-size: 10px; text-transform: uppercase;">
                                    {{ $log->entity_type }}
                                </span>
                            </td>
                            <td class="strong">{{ $log->reference }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $log->action)) }}</td>
                            <td>
                                <span class="pill pill-{{ $log->statusTone() }}">
                                    {{ ucfirst($log->status) }}
                                </span>
                            </td>
                            <td style="max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--muted);">
                                {{ $log->message ?: '—' }}
                            </td>
                            <td class="ta-c">
                                @if($log->status === 'failed')
                                    @can('zoho.sync')
                                    <form action="{{ route('zoho.retry', $log) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button class="btn btn-soft btn-sm" type="submit" title="Retry synchronization">
                                            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-refresh"/></svg>
                                            Retry
                                        </button>
                                    </form>
                                    @else
                                    <span style="color: var(--muted); font-size: 10px;">Failed</span>
                                    @endcan
                                @else
                                    <span style="color: var(--muted); font-size: 11px;">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="ta-c" style="padding: 32px; color: var(--muted);">
                                No sync log entries match the selected filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div style="padding: 12px 16px;">
                {{ $logs->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
