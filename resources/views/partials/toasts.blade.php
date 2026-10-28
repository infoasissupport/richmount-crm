<div class="toasts" id="toasts" aria-live="polite">
    @if(session('toast_success'))
        <div class="toast ok show">
            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check-circle"/></svg>
            <span>{{ session('toast_success') }}</span>
        </div>
    @endif

    @if(session('toast_error'))
        <div class="toast error show">
            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-alert"/></svg>
            <span>{{ session('toast_error') }}</span>
        </div>
    @endif

    @if(session('toast_info'))
        <div class="toast info show">
            <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-info"/></svg>
            <span>{{ session('toast_info') }}</span>
        </div>
    @endif

    @if(isset($errors) && $errors->any())
        @foreach($errors->all() as $err)
            <div class="toast error show">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-alert"/></svg>
                <span>{{ $err }}</span>
            </div>
        @endforeach
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toasts = document.querySelectorAll('.toasts .toast.show');
        toasts.forEach(function (t) {
            setTimeout(function () {
                t.classList.remove('show');
                setTimeout(function () { t.remove(); }, 300);
            }, 4000);
        });
    });

    window.showToast = function (message, type) {
        type = type || 'ok';
        var container = document.getElementById('toasts');
        if (!container) return;
        var toast = document.createElement('div');
        toast.className = 'toast ' + type + ' show';
        var iconId = type === 'error' ? '#i-alert' : (type === 'info' ? '#i-info' : '#i-check-circle');
        toast.innerHTML = '<svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="' + iconId + '"/></svg><span>' + message + '</span>';
        container.appendChild(toast);
        setTimeout(function () {
            toast.classList.remove('show');
            setTimeout(function () { toast.remove(); }, 300);
        }, 4000);
    };
</script>
