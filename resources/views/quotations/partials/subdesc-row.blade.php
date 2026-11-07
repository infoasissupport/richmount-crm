{{--
    Sub-description row: an extra description-only line that belongs to the item row above it.
    Shares the parent item's qty / unit / unit price / total — no pricing cells here.
    Saved with type=subdesc (QuotationService). Needs: $prefix (e.g. items[3]), $value.
--}}
<tr class="subdesc-row">
    <td>
        @include('quotations.partials.rte', ['name' => "{$prefix}[description]", 'value' => $value, 'placeholder' => 'Additional description / scope'])
        <input type="hidden" name="{{ $prefix }}[type]" value="subdesc">
    </td>
    <td class="ta-c" style="white-space: nowrap;">
        <div class="row-actions" style="display: inline-flex; gap: 3px; align-items: center; justify-content: center;">
            <button type="button" class="act" style="width: 26px; height: 26px; border-radius: 6px;" onclick="moveRowUp(this)" title="Move up" aria-label="Move up">
                <svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow-up"/></svg>
            </button>
            <button type="button" class="act" style="width: 26px; height: 26px; border-radius: 6px;" onclick="moveRowDown(this)" title="Move down" aria-label="Move down">
                <svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow-down"/></svg>
            </button>
            <button type="button" class="act" style="width: 26px; height: 26px; border-radius: 6px;" onclick="removeSubDescRow(this)" title="Remove sub-description" aria-label="Remove sub-description">
                <svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>
    </td>
</tr>
