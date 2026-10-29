{{--
    Line item description editor: Bold, text colour, background colour, clear formatting.
    The hidden .li-desc textarea keeps the original field name, so saving, autosave and the preview read it as before.
    Needs: $name, $value (saved HTML or plain text), $placeholder. Behaviour: rteInit() in the builder script.
--}}
<div class="rte">
    <div class="rte-bar" role="toolbar" aria-label="Text formatting">
        <button type="button" class="rte-btn" data-cmd="bold" title="Bold" aria-label="Bold"><b>B</b></button>
        <span class="rte-split">
            <button type="button" class="rte-btn" data-cmd="foreColor" data-color="#c00000" title="Text colour" aria-label="Text colour">
                <span class="rte-a">A</span><i class="rte-chip" style="background: #c00000"></i>
            </button>
            <label class="rte-pick" title="Choose text colour"><span aria-hidden="true">▾</span><input type="color" value="#c00000" data-for="foreColor" aria-label="Choose text colour"></label>
        </span>
        <span class="rte-split">
            <button type="button" class="rte-btn" data-cmd="hiliteColor" data-color="#fff59d" title="Background colour" aria-label="Background colour">
                <span class="rte-bg" style="background: #fff59d">A</span>
            </button>
            <label class="rte-pick" title="Choose background colour"><span aria-hidden="true">▾</span><input type="color" value="#fff59d" data-for="hiliteColor" aria-label="Choose background colour"></label>
        </span>
        <span class="rte-sep" aria-hidden="true"></span>
        <button type="button" class="rte-btn" data-cmd="removeFormat" title="Clear formatting" aria-label="Clear formatting">
            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
        </button>
    </div>
    <div class="rte-area" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Item description" data-placeholder="{{ $placeholder }}"></div>
    <textarea class="li-desc" name="{{ $name }}" hidden>{{ $value }}</textarea>
</div>
