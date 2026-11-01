<?php
// kasir/navbar.php
$currentPage = basename($_SERVER['PHP_SELF']);
$kasirName = $_SESSION['name'] ?? 'Kasir';
$kasirInitials = strtoupper(substr($kasirName, 0, 1));
?>
<header class="top-nav">
    <div class="nav-container">
        <a href="index.php" class="brand">
            <div class="brand-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <i class="fa-solid fa-cash-register"></i>
            </div>
            <div class="brand-text">
                <span class="brand-title">WebMarket</span>
                <span class="brand-subtitle" style="color: #10b981;">Kasir POS</span>
            </div>
        </a>

        <ul class="nav-menu">
            <li>
                <a href="index.php" class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-house"></i> Beranda
                </a>
            </li>
            <li>
                <a href="transactions.php" class="<?php echo ($currentPage == 'transactions.php' || $currentPage == 'invoice.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-cart-shopping"></i> Transaksi Kasir
                </a>
            </li>
            <li>
                <a href="kasirprofil.php" class="<?php echo ($currentPage == 'kasirprofil.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-gear"></i> Kontrol Akun
                </a>
            </li>
        </ul>

        <div class="nav-user">
            <div class="user-badge">
                <div class="user-avatar" style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);">
                    <?php echo htmlspecialchars($kasirInitials); ?>
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($kasirName); ?></span>
                    <span class="user-role">Kasir</span>
                </div>
            </div>
            <a href="../logout.php" class="btn-logout" title="Keluar dari sistem" onclick="return confirm('Apakah Anda yakin ingin keluar?');">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> <span>Keluar</span>
            </a>
        </div>
    </div>
</header>
