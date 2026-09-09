<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require '../includes/db.php';

$stmt = $pdo->query("
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
<link rel="stylesheet" href="../assets/css/admin.css?v=12">
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
                    <th>Category (English)</th>
                    <th>Category (Arabic)</th>
                    <th>Items</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr data-id="<?= $cat['id'] ?>">
                    <td class="cat-name-en"><?= htmlspecialchars($cat['name_en']) ?></td>
                  <td class="cat-name-ar" dir="rtl"><?= htmlspecialchars($cat['name_ar']) ?></td>
                    <td><span class="item-count-badge"><?= $cat['item_count'] ?> items</span></td>
                    <td>
                        <button class="action-btn edit-btn" data-id="<?= $cat['id'] ?>" data-name-en="<?= htmlspecialchars($cat['name_en']) ?>" data-name-ar="<?= htmlspecialchars($cat['name_ar']) ?>">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                        <button class="action-btn danger delete-btn" data-id="<?= $cat['id'] ?>">
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
                        <input type="text" id="nameEnInput" class="form-control" placeholder="e.g. Pizza">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Arabic Name</label>
                        <input type="text" id="nameArInput" class="form-control" placeholder="مثال: بيتزا" dir="rtl">
                    </div>
                    <button type="submit" class="btn-gold w-100 justify-content-center">Save Category</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    $(document).ready(function () {
        $('#categoriesTable').DataTable({
            pageLength: 5,
            lengthMenu: [5, 10, 25, 50],
            columnDefs: [{ orderable: false, targets: 3 }]
        });
    });

    const categoryModalEl = document.getElementById('categoryModal');
    const categoryModal = new bootstrap.Modal(categoryModalEl);
    const categoryForm = document.getElementById('categoryForm');
    const modalTitle = document.getElementById('categoryModalTitle');
    const nameEnInput = document.getElementById('nameEnInput');
    const nameArInput = document.getElementById('nameArInput');
    const editIdInput = document.getElementById('editIdInput');

    document.getElementById('addCategoryBtn').addEventListener('click', function () {
        modalTitle.textContent = 'Add Category';
        editIdInput.value = '';
        nameEnInput.value = '';
        nameArInput.value = '';
        categoryModal.show();
    });

    document.getElementById('categoriesTable').addEventListener('click', function (e) {
        const editBtn = e.target.closest('.edit-btn');
        const deleteBtn = e.target.closest('.delete-btn');

        if (editBtn) {
            modalTitle.textContent = 'Edit Category';
            editIdInput.value = editBtn.dataset.id;
            nameEnInput.value = editBtn.dataset.nameEn;
            nameArInput.value = editBtn.dataset.nameAr;
            categoryModal.show();
        }
if (deleteBtn) {
    if (!confirm('Delete this category?')) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', deleteBtn.dataset.id);

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
        formData.append('name_ar', nameArInput.value);
        if (isEdit) formData.append('id', editIdInput.value);

        fetch('category_actions.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) location.reload();
                else alert(data.message);
            })
            .catch(() => alert('Something went wrong.'));
    });
</script>

</body>
</html>