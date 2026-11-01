<?php
session_start();
include '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'kasir') {
    header('Location: ../login.php');
    exit;
}

$name = $_SESSION['name'] ?? 'Kasir';

// Query data ringkasan untuk kasir
$totalTransactions = 0;
$totalProducts = 0;

$qTrx = $conn->query("SELECT COUNT(DISTINCT transaction_code) as total FROM transactions");
if ($qTrx) $totalTransactions = $qTrx->fetch_assoc()['total'];

$qProd = $conn->query("SELECT COUNT(*) as total FROM products WHERE stock > 0");
if ($qProd) $totalProducts = $qProd->fetch_assoc()['total'];

// Query 5 transaksi terakhir
$recentTrx = $conn->query("
    SELECT transaction_code, buyer_name, SUM(total) as grand_total, COUNT(*) as items_count, MAX(created_at) as trx_date
    FROM transactions
    GROUP BY transaction_code, buyer_name
    ORDER BY MAX(created_at) DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kasir - WebMarket POS</title>
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
                <h1>Halo, <?php echo htmlspecialchars($name); ?> 👋</h1>
                <p>Mesin kasir siap melayani transaksi penjualan toko hari ini.</p>
            </div>
            <div class="page-header-actions">
                <a href="transactions.php" class="btn btn-success btn-lg">
                    <i class="fa-solid fa-cart-shopping"></i> Buka Kasir / Transaksi Baru
                </a>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <span class="stat-label">Produk Siap Jual</span>
                    <span class="stat-value"><?php echo number_format($totalProducts); ?></span>
                    <span class="stat-subtext">Item dengan stok tersedia</span>
                </div>
                <div class="stat-icon success">
                    <i class="fa-solid fa-boxes-packing"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <span class="stat-label">Total Penjualan</span>
                    <span class="stat-value"><?php echo number_format($totalTransactions); ?></span>
                    <span class="stat-subtext">Nota transaksi tercatat</span>
                </div>
                <div class="stat-icon primary">
                    <i class="fa-solid fa-receipt"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <span class="stat-label">Status Terminal</span>
                    <span class="stat-value" style="color: #10b981; font-size: 1.4rem;">Online</span>
                    <span class="stat-subtext"><i class="fa-solid fa-circle" style="color: #10b981; font-size: 0.6rem;"></i> Kasir aktif bertugas</span>
                </div>
                <div class="stat-icon secondary">
                    <i class="fa-solid fa-cash-register"></i>
                </div>
            </div>
        </div>

        <!-- Hero Action Card -->
        <div class="card" style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); color: #ffffff; padding: 2.25rem; margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
                <div style="max-width: 600px;">
                    <span class="badge" style="background: rgba(255,255,255,0.2); color: #ffffff; margin-bottom: 0.75rem;">
                        <i class="fa-solid fa-bolt"></i> Siap Melayani Pelanggan
                    </span>
                    <h2 style="font-size: 1.75rem; font-weight: 800; margin-bottom: 0.5rem; color: #ffffff;">Mulai Transaksi Pembelian</h2>
                    <p style="color: #e0e7ff; font-size: 0.95rem; line-height: 1.5;">
                        Input belanjaan pelanggan dengan cepat, hitung total belanja otomatis, dan cetak struk pembayaran langsung.
                    </p>
                </div>
                <a href="transactions.php" class="btn btn-success btn-lg" style="font-size: 1.1rem; padding: 1rem 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                    <i class="fa-solid fa-plus"></i> Transaksi Kasir
                </a>
            </div>
        </div>

        <!-- Quick Navigation -->
        <div class="quick-actions">
            <a href="transactions.php" class="action-card">
                <div class="action-icon" style="background: var(--success-light); color: var(--success);">
                    <i class="fa-solid fa-cart-arrow-down"></i>
                </div>
                <div class="action-info">
                    <h3>Kasir Penjualan</h3>
                    <p>Buka keranjang belanja dan proses pembayaran pembeli.</p>
                </div>
            </a>

            <a href="kasirprofil.php" class="action-card">
                <div class="action-icon" style="background: #e0f2fe; color: var(--secondary);">
                    <i class="fa-solid fa-key"></i>
                </div>
                <div class="action-info">
                    <h3>Pengaturan Akun</h3>
                    <p>Ganti kata sandi login kasir untuk proteksi akun Anda.</p>
                </div>
            </a>
        </div>

        <!-- Recent Transactions Table -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fa-solid fa-clock-rotate-left" style="color: var(--primary);"></i>
                    Riwayat Transaksi Penjualan Terakhir
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No. Nota</th>
                            <th>Nama Pembeli</th>
                            <th>Jumlah Item</th>
                            <th>Total Belanja</th>
                            <th>Waktu Transaksi</th>
                            <th style="text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentTrx && $recentTrx->num_rows > 0): ?>
                            <?php while ($trx = $recentTrx->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-primary">
                                            <?php echo htmlspecialchars($trx['transaction_code']); ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($trx['buyer_name']); ?></strong></td>
                                    <td><?php echo $trx['items_count']; ?> barang</td>
                                    <td>
                                        <span style="font-weight: 700; color: #10b981;">
                                            Rp <?php echo number_format($trx['grand_total'], 0, ',', '.'); ?>
                                        </span>
                                    </td>
                                    <td style="color: var(--text-muted); font-size: 0.85rem;">
                                        <?php echo date('d M Y, H:i', strtotime($trx['trx_date'] ?? 'now')); ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="invoice.php?transaction_code=<?php echo urlencode($trx['transaction_code']); ?>" class="btn btn-outline btn-sm">
                                            <i class="fa-solid fa-receipt"></i> Lihat Struk
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                                    <i class="fa-solid fa-receipt" style="font-size: 2rem; margin-bottom: 0.5rem; display: block; opacity: 0.5;"></i>
                                    Belum ada riwayat transaksi. Klik "Buka Kasir / Transaksi Baru" untuk memulai penjualan.
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
