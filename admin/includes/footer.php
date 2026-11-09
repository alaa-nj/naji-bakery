<?php if (!function_exists('e')) { http_response_code(403); exit; } // only usable when included by an admin page ?>
<div id="toastContainer"></div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.11/js/jquery.dataTables.min.js"></script>
<script>
    document.getElementById('sidebarToggle').addEventListener('click', function () {
        document.body.classList.toggle('sidebar-collapsed');

        const logo = document.getElementById('sidebarLogo');
        if (document.body.classList.contains('sidebar-collapsed')) {
            logo.src = '../uploads/cropped_circle_image.png';
        } else {
            logo.src = '../uploads/logo-dark.png';
        }
    });

    // Tablets/phones: the open sidebar floats over the page, so tapping
    // anywhere outside it closes it again.
    const smallScreen = window.matchMedia('(max-width: 991.98px)');
    document.addEventListener('click', function (e) {
        if (smallScreen.matches && !document.body.classList.contains('sidebar-collapsed')
            && !e.target.closest('.sidebar')) {
            document.getElementById('sidebarToggle').click();
        }
    });

    // Every DataTable: search/length on top, the table in its own scroll box
    // (sticky header, sideways scroll on phones), info/pagination below.
    if (window.jQuery && $.fn.dataTable) {
        $.extend(true, $.fn.dataTable.defaults, {
            dom: '<"dt-top"lf><"table-scroll"t><"dt-bottom"ip>',
            autoWidth: false
        });
    }

    function showToast(message, type = 'success') {
        const existing = document.querySelector('.toast-notification');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = 'toast-notification' + (type === 'error' ? ' error' : '');
        // Build with textContent (not innerHTML) so a message can never inject HTML/scripts
        const icon = document.createElement('i');
        icon.className = 'bi ' + (type === 'error' ? 'bi-x-circle-fill' : 'bi-check-circle-fill');
        const text = document.createElement('span');
        text.textContent = message;
        toast.append(icon, text);
        document.getElementById('toastContainer').appendChild(toast);

        requestAnimationFrame(() => toast.classList.add('show'));

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Case 1: toast triggered via URL param (used after a redirect, e.g. item_form.php)
    const params = new URLSearchParams(window.location.search);
    if (params.get('saved') === '1') {
        showToast('Saved successfully.');
        params.delete('saved');
    }
    if (params.get('error') === 'notfound') {
        showToast('That item was not found. It may have been deleted.', 'error');
        params.delete('error');
    }
    if (window.location.search !== (params.toString() ? '?' + params.toString() : '')) {
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.replaceState({}, '', newUrl);
    }

    // Case 2: toast triggered via sessionStorage (used after location.reload(), e.g. categories.php)
    const storedToast = sessionStorage.getItem('toastMessage');
    if (storedToast) {
        showToast(storedToast);
        sessionStorage.removeItem('toastMessage');
    }
</script>
</body>
</html>