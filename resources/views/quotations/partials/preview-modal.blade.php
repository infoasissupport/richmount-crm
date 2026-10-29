{{--
    Quotation preview popup (lead page, Quotations list). Any element with data-quote-preview='{"number","doc","pdf","output"}'
    opens it: the quotation document page in a frame, format tabs, Download (PDF in the same format) and Print.
    Include once per page.
--}}
<div class="modal-wrap" id="quotePreview" style="display: none;" onclick="if(event.target === this) closeQuotePreview()">
    <div class="modal modal-lg quote-preview" role="dialog" aria-modal="true" aria-labelledby="quotePreviewTitle">
        <div class="modal-head">
            <div>
                <h3 id="quotePreviewTitle">Quotation</h3>
            </div>
            <button class="icon-btn" type="button" onclick="closeQuotePreview()" aria-label="Close">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="seg" role="radiogroup" aria-label="Quotation format">
                <label><input type="radio" name="qpOutput" value="tax" checked><span>Total + Tax</span></label>
                <label><input type="radio" name="qpOutput" value="notax"><span>Without Tax</span></label>
                <label><input type="radio" name="qpOutput" value="tech"><span>Technical</span></label>
            </div>
            <iframe id="quotePreviewFrame" class="quote-preview-frame" title="Quotation preview"></iframe>
        </div>
        <div class="modal-foot">
            <a class="btn btn-ghost" id="quotePreviewDownload">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-download"/></svg>
                Download
            </a>
            <button class="btn btn-primary" type="button" onclick="document.getElementById('quotePreviewFrame').contentWindow.print()">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-printer"/></svg>
                Print
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Quotation preview popup: shows the quotation document page (builder preview design); the format tabs reload it,
    // Download gets the PDF in the same format and Print prints the document shown
    var quotePreviewData = null;
    function showQuotePreview(output) {
        var q = '?output=' + encodeURIComponent(output);
        document.getElementById('quotePreviewFrame').src = quotePreviewData.doc + q;
        document.getElementById('quotePreviewDownload').href = quotePreviewData.pdf + q;
    }
    function openQuotePreview(data) {
        quotePreviewData = data;
        document.getElementById('quotePreviewTitle').textContent = 'Preview • ' + data.number;
        var output = ['tax', 'notax', 'tech'].indexOf(data.output) !== -1 ? data.output : 'tax';
        document.querySelector('input[name="qpOutput"][value="' + output + '"]').checked = true;
        showQuotePreview(output);
        var m = document.getElementById('quotePreview');
        m.style.display = 'flex';
        m.classList.add('open');
    }
    function closeQuotePreview() {
        var m = document.getElementById('quotePreview');
        m.classList.remove('open');
        m.style.display = 'none';
        document.getElementById('quotePreviewFrame').src = 'about:blank';
    }
    document.addEventListener('click', function (e) {
        var v = e.target.closest('[data-quote-preview]');
        if (!v) return;
        e.preventDefault();
        if (typeof closeRowMenu === 'function') closeRowMenu();
        openQuotePreview(JSON.parse(v.dataset.quotePreview));
    });
    document.addEventListener('change', function (e) {
        if (e.target.name === 'qpOutput' && quotePreviewData) showQuotePreview(e.target.value);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && document.getElementById('quotePreview').style.display !== 'none') closeQuotePreview();
    });
</script>
@endpush
