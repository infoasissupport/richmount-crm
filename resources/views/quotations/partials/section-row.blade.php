{{--
    Section heading row in the builder's line items (e.g. PRELIMINARIES): a title across the table, no qty / price.
    Saved with type=section (QuotationService::sectionRow). Needs: $prefix (e.g. items[3]), $value.
--}}
<tr class="section-row">
    <td class="li-no" aria-hidden="true" style="text-align: center;">
        <span style="display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 4px; background: #FFF0F2; color: #C00000;" title="Section Heading">
            <svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="#i-menu"/></svg>
        </span>
    </td>
    <td colspan="5">
        <input class="li-in li-section" list="sectionSuggestions" name="{{ $prefix }}[description]" value="{{ $value }}" maxlength="200" placeholder="Section heading, e.g. PRELIMINARIES" aria-label="Section heading">
        <input type="hidden" name="{{ $prefix }}[type]" value="section">
    </td>
    <td class="ta-c" style="white-space: nowrap;">
        <div class="row-actions" style="display: inline-flex; gap: 3px; align-items: center; justify-content: center;">
            <button type="button" class="act" style="width: 26px; height: 26px; border-radius: 6px;" onclick="moveRowUp(this)" title="Move up" aria-label="Move up">
                <svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow-up"/></svg>
            </button>
            <button type="button" class="act" style="width: 26px; height: 26px; border-radius: 6px;" onclick="moveRowDown(this)" title="Move down" aria-label="Move down">
                <svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow-down"/></svg>
            </button>
            <button type="button" class="act" style="width: 26px; height: 26px; border-radius: 6px;" onclick="removeSectionRow(this)" title="Remove section" aria-label="Remove section">
                <svg class="ic xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>
    </td>
</tr>
