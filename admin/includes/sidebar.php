<div class="sidebar">
    <div class="sidebar-brand-row">
        <img src="../uploads/logo-dark.png" alt="Naji Bakery" id="sidebarLogo">
        <button id="sidebarToggle" type="button">
            <i class="bi bi-list"></i>
        </button>
    </div>

    <div class="sidebar-nav">
    <a href="dashboard.php" class="sidebar-link <?= $activePage === 'dashboard' ? 'active' : '' ?>" title="Dashboard"><i class="bi bi-grid-1x2-fill"></i> <span>Dashboard</span></a>
    <a href="categories.php" class="sidebar-link <?= $activePage === 'categories' ? 'active' : '' ?>" title="Categories"><i class="bi bi-tags-fill"></i> <span>Categories</span></a>
    <a href="items.php" class="sidebar-link <?= $activePage === 'items' ? 'active' : '' ?>" title="Menu Items"><i class="bi bi-card-list"></i> <span>Menu Items</span></a>
    <a href="qr.php" class="sidebar-link <?= $activePage === 'qr' ? 'active' : '' ?>" title="QR Code"><i class="bi bi-qr-code-scan"></i> <span>QR Code</span></a>
    <a href="../public/index.php" target="_blank" class="sidebar-link" title="View Public Menu"><i class="bi bi-eye-fill"></i> <span>View Public Menu</span></a>
</div>

    <div class="sidebar-footer">
    <div class="sidebar-footer-avatar"><?= strtoupper(substr($_SESSION['admin_username'], 0, 1)) ?></div>
    <div class="sidebar-footer-text">
        <p class="username mb-0"><?= htmlspecialchars($_SESSION['admin_username']) ?></p>
        <a href="logout.php" title="Log out">Log out</a>
    </div>
</div>
</div>