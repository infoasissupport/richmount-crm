{{--
    KPI tiles (dashboard, Lead Management). Needs $kpis from DashboardService (key, label, value, icon, trend, href).
    Optional: $gridClass, e.g. "four" for a four-column row.
--}}
<section class="kpi-grid {{ $gridClass ?? '' }}" id="kpis" aria-label="Key figures">
    @foreach($kpis as $kpi)
        <a class="kpi" href="{{ $kpi['href'] }}" data-kpi="{{ $kpi['key'] }}" title="Open {{ $kpi['label'] }}">
            <span class="kpi-top">
                <span class="kpi-icon">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
                        <use href="#i-{{ $kpi['icon'] }}"/>
                    </svg>
                </span>
                @if(!empty($kpi['trend']))
                    <span class="kpi-trend {{ $kpi['trend']['direction'] }}" title="vs same period last year">
                        <svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
                            <use href="#i-arrow-{{ $kpi['trend']['direction'] }}"/>
                        </svg>
                        {{ $kpi['trend']['pct'] }}%
                    </span>
                @endif
            </span>
            <span class="kpi-value">{{ $kpi['value'] }}</span>
            <span class="kpi-label">{{ $kpi['label'] }}</span>
        </a>
    @endforeach
</section>
