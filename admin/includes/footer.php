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

    function showToast(message, type = 'success') {
        const existing = document.querySelector('.toast-notification');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = 'toast-notification' + (type === 'error' ? ' error' : '');
        toast.innerHTML = `
            <i class="bi ${type === 'error' ? 'bi-x-circle-fill' : 'bi-check-circle-fill'}"></i>
            <span>${message}</span>
        `;
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