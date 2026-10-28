{{--
    App-wide paginator view (set as the default in AppServiceProvider), styled like the dashboard Recent Leads pager.
    Used through $paginator->links(); always shows the "Showing x–y of z" line, buttons are disabled on a single page.
    Pass ['pagerId' => '...'] to links() for a pager inside a bordered box (e.g. #recentPager) instead of the strip under a table.
--}}
@php
    $chevron = fn (bool $flip) => '<svg class="ic sm' . ($flip ? ' flip' : '') . '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>';
@endphp
<nav @if(!empty($pagerId)) id="{{ $pagerId }}" class="pager" @else class="pager dt-pager" @endif role="navigation" aria-label="Pagination">
    <span class="pager-info">
        @if ($paginator->total() > 0)
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        @else
            No records
        @endif
    </span>
    <span class="pager-btns">
        @if ($paginator->onFirstPage())
            <button type="button" class="btn btn-ghost btn-sm" disabled aria-label="Previous page">{!! $chevron(true) !!}</button>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-ghost btn-sm" rel="prev" aria-label="Previous page">{!! $chevron(true) !!}</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pager-gap" aria-hidden="true">{{ $element }}</span>
            @elseif (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <button type="button" class="btn btn-ghost btn-sm pager-num active" aria-current="page">{{ $page }}</button>
                    @else
                        <a href="{{ $url }}" class="btn btn-ghost btn-sm pager-num" aria-label="Page {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-ghost btn-sm" rel="next" aria-label="Next page">{!! $chevron(false) !!}</a>
        @else
            <button type="button" class="btn btn-ghost btn-sm" disabled aria-label="Next page">{!! $chevron(false) !!}</button>
        @endif
    </span>
</nav>
