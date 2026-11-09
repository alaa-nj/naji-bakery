<?php
require '../includes/auth.php';

require '../includes/db.php';

$stmt = db_query("
    SELECT m.*, c.name_en AS category_name_en, c.name_ar AS category_name_ar
    FROM menu_items m
    JOIN categories c ON c.id = m.category_id
    ORDER BY c.display_order ASC, m.name_ar ASC
");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalItems = count($items);
$totalCategories = db_query("SELECT COUNT(*) FROM categories")->fetchColumn();
$allCategories = db_query("SELECT * FROM categories ORDER BY display_order ASC")->fetchAll(PDO::FETCH_ASSOC);

$activePage = 'items';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Menu Items - Naji Bakery Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.11/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="../assets/css/admin.css?v=<?= asset_version(dirname(__DIR__) . '/assets/css/admin.css') ?>">
<style>
    .form-check-input:checked { background-color: #C89B2B; border-color: #C89B2B; }

    .filter-bar {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        margin-bottom: 1.25rem;
        flex-wrap: wrap;
    }

    .filter-btn {
        background: #fff;
        border: 1.5px solid #e5ddc8;
        color: #1A1A1A;
        border-radius: 20px;
        padding: 0.4rem 1rem;
        font-size: 0.85rem;
        font-weight: 500;
        transition: 0.2s ease;
        cursor: pointer;
    }
    .filter-btn:hover { border-color: #C89B2B; }
    .filter-btn.active {
        background: linear-gradient(135deg, #A67C1E, #C89B2B);
        color: #1A1A1A;
        border-color: transparent;
        font-weight: 600;
    }
</style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">

    <div class="page-header">
        <div>
            <h3 class="page-title brand-font">Menu Items</h3>
            <p class="page-sub"><?= e($totalItems) ?> items across <?= e($totalCategories) ?> categories.</p>
        </div>
        <a href="item_form.php" class="btn-gold"><i class="bi bi-plus-lg"></i> Add Menu Item</a>
    </div>

    <div class="filter-bar">
        <button class="filter-btn active" data-filter="all">All Items</button>
        <button class="filter-btn" data-filter="available">Available</button>
        <button class="filter-btn" data-filter="hidden">Hidden</button>
        <button class="filter-btn" data-filter="noprice">Missing Price</button>

        <select id="categoryFilter" class="form-select form-select-sm" style="width: auto; border-radius: 20px; border: 1.5px solid #e5ddc8;">
            <option value="">All Categories</option>
            <?php foreach ($allCategories as $cat): ?>
                <option value="<?= e($cat['name_en']) ?>"><?= e($cat['name_en']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="card-soft p-3">
        <table id="itemsTable" class="display" style="width:100%">
            <thead>
                <tr>
                    <th></th>
                    <th>Item</th>
                    <th>Category</th>
                    <th class="col-nowrap">Price</th>
                    <th>Available</th>
                    <th class="col-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr data-id="<?= e($item['id']) ?>"
                    data-available="<?= $item['is_available'] ? '1' : '0' ?>"
                    data-noprice="<?= ($item['price'] === null || $item['price'] == 0) ? '1' : '0' ?>"
                    data-category="<?= e($item['category_name_en']) ?>">
                    <td>
                        <div class="item-thumb">
                            <?php if (!empty($item['image_path'])): ?>
                                <img src="../<?= e($item['image_path']) ?>" alt="">
                            <?php else: ?>
                                <i class="bi bi-image"></i>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <?php if (!$item['is_available']): ?>
                            <span class="badge-unavailable">Hidden</span>
                        <?php endif; ?>
                        <?php if ($item['price'] === null || $item['price'] == 0): ?>
                            <span class="badge-noprice">No price</span>
                        <?php endif; ?>
                        <div class="item-name-ar"><?= e($item['name_ar']) ?></div>
                    </td>
                    <td><span class="category-pill"><?= e($item['category_name_en']) ?></span></td>
                    <td class="col-nowrap">
                        <?php if ($item['price'] === null || $item['price'] == 0): ?>
                            —
                        <?php else: ?>
                            <?= number_format($item['price']) ?> <?= $item['currency'] === 'USD' ? '$' : 'L.L.' ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="form-check form-switch">
                            <input class="form-check-input availability-toggle" type="checkbox"
                                   data-id="<?= e($item['id']) ?>"
                                   <?= $item['is_available'] ? 'checked' : '' ?>>
                        </div>
                    </td>
                    <td class="col-actions">
                        <a href="item_form.php?id=<?= e($item['id']) ?>" class="action-btn" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                        <button class="action-btn danger delete-item-btn" data-id="<?= e($item['id']) ?>" title="Delete"><i class="bi bi-trash-fill"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<?php include 'includes/footer.php'; ?>

<script>
    const CSRF_TOKEN = "<?= e($_SESSION['csrf_token']) ?>";

    $(document).ready(function () {
        const table = $('#itemsTable').DataTable({
            pageLength: 5,
            lengthMenu: [5, 10, 25, 50, 100, 150, 200, 250, 300, 350],
            columnDefs: [
                { orderable: false, targets: [0, 4, 5] }
            ]
        });

        let currentStatusFilter = 'all';
        let currentCategoryFilter = '';

        // Registered ONCE — reads the current filter state on every redraw
        $.fn.dataTable.ext.search.push(function (settings, data, rowIndex) {
            const row = table.row(rowIndex).node();

            let statusMatch = true;
            if (currentStatusFilter === 'available') statusMatch = row.dataset.available === '1';
            else if (currentStatusFilter === 'hidden') statusMatch = row.dataset.available === '0';
            else if (currentStatusFilter === 'noprice') statusMatch = row.dataset.noprice === '1';

            let categoryMatch = true;
            if (currentCategoryFilter !== '') categoryMatch = row.dataset.category === currentCategoryFilter;

            return statusMatch && categoryMatch;
        });

        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentStatusFilter = this.dataset.filter;
                table.draw();
            });
        });

        document.getElementById('categoryFilter').addEventListener('change', function () {
            currentCategoryFilter = this.value;
            table.draw();
        });

        // Opened from the dashboard's "Fix now" link: start on the Missing Price filter
        if (new URLSearchParams(window.location.search).get('filter') === 'missing_price') {
            document.querySelector('.filter-btn[data-filter="noprice"]').click();
        }

        // Availability toggle — event delegation since DataTables re-renders rows
        document.getElementById('itemsTable').addEventListener('change', function (e) {
            if (!e.target.classList.contains('availability-toggle')) return;

            const id = e.target.dataset.id;
            const isAvailable = e.target.checked ? 1 : 0;

            const formData = new FormData();
            formData.append('action', 'toggle_availability');
            formData.append('id', id);
            formData.append('is_available', isAvailable);
            formData.append('csrf_token', CSRF_TOKEN);

            fetch('item_actions.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) showToast(isAvailable ? 'Item marked available.' : 'Item hidden.');
                    else {
                        showToast(data.message, 'error');
                        e.target.checked = !e.target.checked; // revert the switch since it didn't actually save
                    }
                })
                .catch(() => {
                    showToast('Something went wrong. Please try again.', 'error');
                    e.target.checked = !e.target.checked;
                });
        });

        // Delete item — event delegation
        document.getElementById('itemsTable').addEventListener('click', function (e) {
            const deleteBtn = e.target.closest('.delete-item-btn');
            if (!deleteBtn) return;

            if (!confirm('Delete this item permanently?')) return;
            const id = deleteBtn.dataset.id;

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            formData.append('csrf_token', CSRF_TOKEN);

            fetch('item_actions.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        sessionStorage.setItem('toastMessage', data.message || 'Item deleted.');
                        location.reload();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(() => showToast('Something went wrong. Please try again.', 'error'));
        });
    });
</script>

</body>
</html>