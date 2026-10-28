/* Rich Mount ERP — styled upload area for every <input type="file">.
   Port of enhanceFileInput() from the prototype's core.js; styles live in style.css (.file-drop / .fd-*).
   Add data-plain to an input to opt out. */
(function () {
  'use strict';

  var esc = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };
  var icon = function (name, cls) {
    return '<svg class="' + (cls || 'ic') + '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-' + name + '"/></svg>';
  };
  var fmtSize = function (b) {
    return b < 1024 ? b + ' B' : b < 1048576 ? Math.round(b / 1024) + ' KB' : (b / 1048576).toFixed(1) + ' MB';
  };

  function enhanceFileInput(input) {
    if (input.dataset.enhanced) return;
    input.dataset.enhanced = '1';
    var types = (input.accept || '').split(',').map(function (t) { return t.trim(); }).filter(Boolean)
      .map(function (t) { return t === 'image/*' ? 'Images' : t.replace(/^\./, '').toUpperCase(); }).join(', ');
    var box = document.createElement('div');
    box.className = 'file-drop';
    box.innerHTML = '<span class="fd-icon">' + icon('upload') + '</span><span class="fd-text"><span><strong>Click to upload</strong> or drag &amp; drop</span><small>' +
      esc(types || 'Any file type') + (input.multiple ? ' · multiple files allowed' : '') + '</small></span>';
    var list = document.createElement('ul');
    list.className = 'fd-list';
    input.parentNode.insertBefore(box, input);
    box.appendChild(input);
    box.after(list);

    var paint = function () {
      var files = Array.prototype.slice.call(input.files || []);
      list.innerHTML = files.map(function (f) {
        return '<li>' + icon('file-text', 'ic sm') + '<span>' + esc(f.name) + '</span><small>' + fmtSize(f.size) + '</small></li>';
      }).join('') + (files.length ? '<li class="fd-clear"><button type="button" class="link-btn">' + icon('x', 'ic xs') + 'Clear</button></li>' : '');
      box.classList.toggle('has-files', files.length > 0);
    };
    input.addEventListener('change', function () { setTimeout(paint); }); // after any page handler that resets the input
    input.addEventListener('dragenter', function () { box.classList.add('drag'); });
    ['dragleave', 'drop'].forEach(function (ev) { input.addEventListener(ev, function () { box.classList.remove('drag'); }); });
    list.addEventListener('click', function (e) {
      if (!e.target.closest('.fd-clear button')) return;
      e.preventDefault();
      input.value = '';
      input.dispatchEvent(new Event('change', { bubbles: true }));
    });
    // forms reset by page scripts (e.g. reopening a modal) should drop the old file list too
    if (input.form) input.form.addEventListener('reset', function () { setTimeout(paint); });
  }

  window.enhanceFileInputs = function (root) {
    (root || document).querySelectorAll('input[type=file]:not([data-plain])').forEach(enhanceFileInput);
  };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { window.enhanceFileInputs(); });
  else window.enhanceFileInputs();
})();
