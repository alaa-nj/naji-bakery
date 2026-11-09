<?php
require '../includes/auth.php';

require '../includes/db.php';
require '../includes/category_icons.php';

$stmt = db_query("
    SELECT c.*, COUNT(m.id) AS item_count
    FROM categories c
    LEFT JOIN menu_items m ON m.category_id = c.id
    GROUP BY c.id
    ORDER BY c.display_order ASC
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$activePage = 'categories';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Categories - Naji Bakery Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.11/css/jquery.dataTables.min.css">
<!-- Font Awesome: only to preview the category icons shown on the public menu -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
    .cat-icon-cell {
        width: 38px; height: 38px;
        border-radius: 10px;
        background: #FDFBF6;
        color: #C89B2B;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
    }
    .icon-picker {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .icon-picker .icon-preview {
        width: 48px; height: 48px;
        flex-shrink: 0;
        border-radius: 12px;
        border: 1.5px solid #e5ddc8;
        background: #FDFBF6;
        color: #C89B2B;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
    }
</style>
<link rel="stylesheet" href="../assets/css/admin.css?v=<?= asset_version(dirname(__DIR__) . '/assets/css/admin.css') ?>">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">

    <div class="page-header">
        <div>
            <h3 class="page-title brand-font">Categories</h3>
            <p class="page-sub">Organize your menu into sections.</p>
        </div>
        <button class="btn-gold" id="addCategoryBtn"><i class="bi bi-plus-lg"></i> Add Category</button>
    </div>

    <div class="card-soft p-3">
        <table id="categoriesTable" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>Icon</th>
                    <th>Category (English)</th>
                    <th>Category (Arabic)</th>
                    <th>Items</th>
                    <th class="col-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr data-id="<?= e($cat['id']) ?>">
                    <td><span class="cat-icon-cell" title="<?= e(CATEGORY_ICONS[category_icon_class($cat['icon'])]) ?>"><i class="fa-solid <?= e(category_icon_class($cat['icon'])) ?>"></i></span></td>
                    <td class="cat-name-en"><?= e($cat['name_en']) ?></td>
                  <td class="cat-name-ar" dir="rtl"><?= e($cat['name_ar']) ?></td>
                    <td class="col-nowrap"><span class="item-count-badge"><?= e($cat['item_count']) ?> items</span></td>
                    <td class="col-actions">
                        <button class="action-btn move-up-btn" data-id="<?= e($cat['id']) ?>" title="Move up">
                            <i class="bi bi-arrow-up"></i>
                        </button>
                        <button class="action-btn move-down-btn" data-id="<?= e($cat['id']) ?>" title="Move down">
                            <i class="bi bi-arrow-down"></i>
                        </button>
                        <button class="action-btn edit-btn" title="Edit" data-id="<?= e($cat['id']) ?>" data-name-en="<?= e($cat['name_en']) ?>" data-name-ar="<?= e($cat['name_ar']) ?>" data-icon="<?= e(category_icon_class($cat['icon'])) ?>">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                        <button class="action-btn danger delete-btn" data-id="<?= e($cat['id']) ?>" title="Delete">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:420px;">
        <div class="modal-content" style="border-radius:16px; border:none;">
            <div class="modal-header" style="border-bottom:1px solid #f2ead4;">
                <h5 class="modal-title brand-font" id="categoryModalTitle">Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    <input type="hidden" id="editIdInput">
                    <div class="mb-3">
                        <label class="form-label small text-muted">English Name</label>
                        <input type="text" id="nameEnInput" class="form-control" placeholder="e.g. Pizza" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Arabic Name</label>
                        <input type="text" id="nameArInput" class="form-control" placeholder="مثال: بيتزا" dir="rtl" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted" for="iconSelect">Icon (shown on the public menu)</label>
                        <div class="icon-picker">
                            <span class="icon-preview" aria-hidden="true"><i class="fa-solid <?= e(CATEGORY_ICON_DEFAULT) ?>" id="iconPreview"></i></span>
                            <select id="iconSelect" class="form-select">
                                <?php foreach (CATEGORY_ICONS as $iconClass => $iconLabel): ?>
                                    <option value="<?= e($iconClass) ?>" <?= $iconClass === CATEGORY_ICON_DEFAULT ? 'selected' : '' ?>><?= e($iconLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn-gold w-100 justify-content-center">Save Category</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    const CSRF_TOKEN = "<?= e($_SESSION['csrf_token']) ?>";

    $(document).ready(function () {
        $('#categoriesTable').DataTable({
            pageLength: 25, // show them all on one page — makes reordering with the arrows easier to follow
            lengthMenu: [5, 10, 25, 50],
            ordering: false, // keep rows in display_order; sortable columns would fight with the up/down arrows
            columnDefs: [{ orderable: false, targets: [0, 4] }]
        });
    });

    const categoryModalEl = document.getElementById('categoryModal');
    const categoryModal = new bootstrap.Modal(categoryModalEl);
    const categoryForm = document.getElementById('categoryForm');
    const modalTitle = document.getElementById('categoryModalTitle');
    const nameEnInput = document.getElementById('nameEnInput');
    const nameArInput = document.getElementById('nameArInput');
    const editIdInput = document.getElementById('editIdInput');
    const iconSelect = document.getElementById('iconSelect');
    const iconPreview = document.getElementById('iconPreview');

    // Live preview of the chosen icon
    function setIcon(iconClass) {
        iconSelect.value = iconClass;
        iconPreview.className = 'fa-solid ' + iconSelect.value;
    }
    iconSelect.addEventListener('change', function () { setIcon(this.value); });

    document.getElementById('addCategoryBtn').addEventListener('click', function () {
        modalTitle.textContent = 'Add Category';
        editIdInput.value = '';
        nameEnInput.value = '';
        nameArInput.value = '';
        setIcon('<?= e(CATEGORY_ICON_DEFAULT) ?>');
        categoryModal.show();
    });

    // Opened from the dashboard's "Add Category" quick action
    if (new URLSearchParams(window.location.search).get('add') === '1') {
        document.getElementById('addCategoryBtn').click();
        window.history.replaceState({}, '', window.location.pathname);
    }

    document.getElementById('categoriesTable').addEventListener('click', function (e) {
        const editBtn = e.target.closest('.edit-btn');
        const deleteBtn = e.target.closest('.delete-btn');
        const upBtn = e.target.closest('.move-up-btn');
        const downBtn = e.target.closest('.move-down-btn');

        if (upBtn || downBtn) {
            const formData = new FormData();
            formData.append('action', upBtn ? 'move_up' : 'move_down');
            formData.append('id', (upBtn || downBtn).dataset.id);
            formData.append('csrf_token', CSRF_TOKEN);

            fetch('category_actions.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        sessionStorage.setItem('toastMessage', 'Order updated.');
                        location.reload();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(() => showToast('Something went wrong. Please try again.', 'error'));
            return;
        }

        if (editBtn) {
            modalTitle.textContent = 'Edit Category';
            editIdInput.value = editBtn.dataset.id;
            nameEnInput.value = editBtn.dataset.nameEn;
            nameArInput.value = editBtn.dataset.nameAr;
            setIcon(editBtn.dataset.icon);
            categoryModal.show();
        }
if (deleteBtn) {
    if (!confirm('Delete this category?')) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', deleteBtn.dataset.id);
    formData.append('csrf_token', CSRF_TOKEN);

    fetch('category_actions.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                sessionStorage.setItem('toastMessage', data.message || 'Category deleted.');
                location.reload();
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(() => showToast('Something went wrong. Please try again.', 'error'));
        }
    });

    categoryForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const isEdit = editIdInput.value !== '';
        const formData = new FormData();
        formData.append('action', isEdit ? 'edit' : 'add');
        formData.append('name_en', nameEnInput.value);
        formData.append('icon', iconSelect.value);
        formData.append('name_ar', nameArInput.value);
        formData.append('csrf_token', CSRF_TOKEN);
        if (isEdit) formData.append('id', editIdInput.value);

        fetch('category_actions.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    sessionStorage.setItem('toastMessage', data.message || 'Saved.');
                    location.reload();
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(() => showToast('Something went wrong. Please try again.', 'error'));
    });
</script>

</body>
</html>