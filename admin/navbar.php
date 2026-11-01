<?php
// admin/navbar.php
$currentPage = basename($_SERVER['PHP_SELF']);
$adminName = $_SESSION['name'] ?? 'Administrator';
$adminInitials = strtoupper(substr($adminName, 0, 1));
?>
<header class="top-nav">
    <div class="nav-container">
        <a href="index.php" class="brand">
            <div class="brand-icon">
                <i class="fa-solid fa-store"></i>
            </div>
            <div class="brand-text">
                <span class="brand-title">WebMarket</span>
                <span class="brand-subtitle">Admin Panel</span>
            </div>
        </a>

        <ul class="nav-menu">
            <li>
                <a href="index.php" class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-pie"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="products.php" class="<?php echo ($currentPage == 'products.php' || $currentPage == 'edit_product.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-boxes-stacked"></i> Kelola Produk
                </a>
            </li>
            <li>
                <a href="add_product.php" class="<?php echo ($currentPage == 'add_product.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-plus-circle"></i> Tambah Produk
                </a>
            </li>
            <li>
                <a href="adminprofil.php" class="<?php echo ($currentPage == 'adminprofil.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-shield-halved"></i> Keamanan
                </a>
            </li>
        </ul>

        <div class="nav-user">
            <div class="user-badge">
                <div class="user-avatar"><?php echo htmlspecialchars($adminInitials); ?></div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($adminName); ?></span>
                    <span class="user-role">Administrator</span>
                </div>
            </div>
            <a href="../logout.php" class="btn-logout" title="Keluar dari sistem" onclick="return confirm('Apakah Anda yakin ingin keluar?');">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> <span>Keluar</span>
            </a>
        </div>
    </div>
</header>
