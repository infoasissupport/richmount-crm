{{-- Line item photos under the description in the quotation PDFs (DomPDF reads them from disk). Needs: $item --}}
@if(!empty($item->photos))
    <div style="margin-top: 5px;">
        @foreach($item->photos as $photo)
            @php $file = storage_path('app/public/' . $photo); @endphp
            @if(is_file($file))
                <img src="{{ $file }}" alt="" style="width: 78px; height: 58px; border: 1px solid #bbbbbb; margin-right: 4px;">
            @endif
        @endforeach
    </div>
@endif
