{{--
    Line item photo strip (up to 3): thumbnails (click to enlarge) + "Add photo". Photos upload right away
    (quotations.line-photo); each keeps a hidden {prefix}[photos][] field so Save and autosave carry it.
    Needs: $prefix (e.g. items[0]), $photos (array of public-disk paths). Behaviour: builder script.
--}}
@php $photos = array_values($photos ?? []); @endphp
<div class="li-photo" data-prefix="{{ $prefix }}">
    @foreach($photos as $n => $path)
        <span class="ph">
            <img src="{{ asset('storage/' . $path) }}" alt="Item photo {{ $n + 1 }}" title="Click to enlarge" data-photo-view>
            <button type="button" data-photo-remove title="Remove photo" aria-label="Remove photo {{ $n + 1 }}">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
            <input type="hidden" name="{{ $prefix }}[photos][]" value="{{ $path }}">
        </span>
    @endforeach
    <label class="ph-add" title="Add up to 3 photos" @if(count($photos) >= 3) hidden @endif>
        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-image"/></svg>
        <span class="ph-add-text">{{ count($photos) ? 'Add' : 'Add photo' }}</span>
        <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-photo-input data-plain aria-label="Add a photo">
    </label>
</div>
