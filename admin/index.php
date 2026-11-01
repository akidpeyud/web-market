<?php
session_start();
include '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$name = $_SESSION['name'] ?? 'Admin';

// Query data statistik dashboard
$totalProducts = 0;
$lowStockCount = 0;
$totalCategories = 0;
$totalTransactions = 0;

$qProd = $conn->query("SELECT COUNT(*) as total FROM products");
if ($qProd) $totalProducts = $qProd->fetch_assoc()['total'];

$qLow = $conn->query("SELECT COUNT(*) as total FROM products WHERE stock < 10");
if ($qLow) $lowStockCount = $qLow->fetch_assoc()['total'];

$qCat = $conn->query("SELECT COUNT(*) as total FROM categories");
if ($qCat) $totalCategories = $qCat->fetch_assoc()['total'];

$qTrx = $conn->query("SELECT COUNT(DISTINCT transaction_code) as total FROM transactions");
if ($qTrx) $totalTransactions = $qTrx->fetch_assoc()['total'];

// Query beberapa produk terbaru
$recentProducts = $conn->query("
    SELECT p.*, c.category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    ORDER BY p.products_id DESC 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - WebMarket</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Main Style -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="main-wrapper">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-text">
                <h1>Selamat Datang, <?php echo htmlspecialchars($name); ?> 👋</h1>
                <p>Ringkasan performa dan manajemen inventaris toko Anda hari ini.</p>
            </div>
            <div class="page-header-actions">
                <a href="add_product.php" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Tambah Produk
                </a>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <span class="stat-label">Total Produk</span>
                    <span class="stat-value"><?php echo number_format($totalProducts); ?></span>
                    <span class="stat-subtext">Item terdaftar di katalog</span>
                </div>
                <div class="stat-icon primary">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <span class="stat-label">Stok Menipis</span>
                    <span class="stat-value" style="<?php echo $lowStockCount > 0 ? 'color: #ef4444;' : ''; ?>">
                        <?php echo number_format($lowStockCount); ?>
                    </span>
                    <span class="stat-subtext">Perlu restock segera (&lt; 10 pcs)</span>
                </div>
                <div class="stat-icon warning">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <span class="stat-label">Kategori Produk</span>
                    <span class="stat-value"><?php echo number_format($totalCategories); ?></span>
                    <span class="stat-subtext">Klasifikasi barang</span>
                </div>
                <div class="stat-icon secondary">
                    <i class="fa-solid fa-tags"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <span class="stat-label">Total Transaksi</span>
                    <span class="stat-value"><?php echo number_format($totalTransactions); ?></span>
                    <span class="stat-subtext">Transaksi berhasil</span>
                </div>
                <div class="stat-icon success">
                    <i class="fa-solid fa-receipt"></i>
                </div>
            </div>
        </div>

        <!-- Quick Actions Navigation -->
        <div class="quick-actions">
            <a href="products.php" class="action-card">
                <div class="action-icon">
                    <i class="fa-solid fa-warehouse"></i>
                </div>
                <div class="action-info">
                    <h3>Katalog & Stok Produk</h3>
                    <p>Lihat, ubah harga, update stok, dan hapus data inventaris barang.</p>
                </div>
            </a>

            <a href="add_product.php" class="action-card">
                <div class="action-icon" style="background: var(--success-light); color: var(--success);">
                    <i class="fa-solid fa-circle-plus"></i>
                </div>
                <div class="action-info">
                    <h3>Tambah Produk Baru</h3>
                    <p>Masukkan varian barang baru, tentukan harga, stok, dan kategorinya.</p>
                </div>
            </a>

            <a href="adminprofil.php" class="action-card">
                <div class="action-icon" style="background: #e0f2fe; color: var(--secondary);">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div class="action-info">
                    <h3>Pengaturan Akun & Sandi</h3>
                    <p>Perbarui kata sandi akun administrator untuk menjaga keamanan sistem.</p>
                </div>
            </a>
        </div>

        <!-- Recent Products Section -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fa-solid fa-clock-rotate-left" style="color: var(--primary);"></i>
                    Produk yang Baru Ditambahkan
                </div>
                <a href="products.php" class="btn btn-outline btn-sm">
                    Lihat Semua Produk <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th>Harga Satuan</th>
                            <th>Stok</th>
                            <th>Status Stok</th>
                            <th style="text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentProducts && $recentProducts->num_rows > 0): ?>
                            <?php while ($prod = $recentProducts->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="badge badge-neutral">#<?php echo $prod['products_id']; ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($prod['name']); ?></strong></td>
                                    <td>
                                        <span class="badge badge-primary">
                                            <?php echo htmlspecialchars($prod['category_name'] ?? 'Umum'); ?>
                                        </span>
                                    </td>
                                    <td>Rp <?php echo number_format($prod['price'], 0, ',', '.'); ?></td>
                                    <td><strong><?php echo $prod['stock']; ?></strong> pcs</td>
                                    <td>
                                        <?php if ($prod['stock'] == 0): ?>
                                            <span class="badge badge-danger"><i class="fa-solid fa-circle-xmark"></i> Habis</span>
                                        <?php elseif ($prod['stock'] < 10): ?>
                                            <span class="badge badge-warning"><i class="fa-solid fa-triangle-exclamation"></i> Menipis</span>
                                        <?php else: ?>
                                            <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> Aman</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="edit_product.php?id=<?php echo $prod['products_id']; ?>" class="btn btn-outline btn-sm" title="Edit Produk">
                                            <i class="fa-regular fa-pen-to-square"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                    <i class="fa-solid fa-box-open" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                                    Belum ada produk yang ditambahkan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer class="app-footer">
        &copy; <?php echo date('Y'); ?> WebMarket POS System. All rights reserved.
    </footer>
</body>
</html>
