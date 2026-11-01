<?php
session_start();
include '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

// Ambil semua produk beserta nama kategorinya
$sql = "SELECT p.*, c.category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        ORDER BY p.products_id DESC";
$result = $conn->query($sql);

$statusMsg = '';
$statusType = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'added') {
        $statusMsg = 'Produk baru berhasil ditambahkan!';
        $statusType = 'success';
    } elseif ($_GET['status'] == 'updated') {
        $statusMsg = 'Data produk berhasil diperbarui!';
        $statusType = 'success';
    } elseif ($_GET['status'] == 'deleted') {
        $statusMsg = 'Produk berhasil dihapus dari database.';
        $statusType = 'success';
    } elseif ($_GET['status'] == 'error') {
        $statusMsg = 'Terjadi kesalahan saat memproses data.';
        $statusType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - WebMarket</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Main Style -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="main-wrapper">
        <div class="page-header">
            <div class="page-header-text">
                <h1>Katalog & Inventaris Produk</h1>
                <p>Kelola daftar barang dagangan, pantau ketersediaan stok, dan sesuaikan harga jual.</p>
            </div>
            <div class="page-header-actions" style="display: flex; gap: 0.75rem;">
                <a href="index.php" class="btn btn-outline">
                    <i class="fa-solid fa-arrow-left"></i> Dashboard
                </a>
                <a href="add_product.php" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Tambah Produk Baru
                </a>
            </div>
        </div>

        <?php if (!empty($statusMsg)): ?>
            <div class="alert alert-<?php echo $statusType; ?>">
                <i class="fa-solid <?php echo $statusType == 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                <span><?php echo htmlspecialchars($statusMsg); ?></span>
            </div>
        <?php endif; ?>

        <div class="card">
            <!-- Search & Filter Controls -->
            <div class="card-header" style="flex-wrap: wrap; gap: 1rem;">
                <div class="card-title">
                    <i class="fa-solid fa-boxes-stacked" style="color: var(--primary);"></i>
                    Daftar Semua Produk
                </div>
                <div style="min-width: 280px; max-width: 400px; width: 100%;">
                    <div class="input-group">
                        <span class="input-icon-left"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" id="searchInput" class="form-control input-with-icon-left" placeholder="Cari nama barang atau kategori...">
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table" id="productsTable">
                    <thead>
                        <tr>
                            <th style="width: 70px;">ID</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th>Harga Satuan</th>
                            <th>Stok</th>
                            <th>Status</th>
                            <th style="text-align: right; width: 160px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="badge badge-neutral">#<?php echo $row['products_id']; ?></span></td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--dark);">
                                            <?php echo htmlspecialchars($row['name']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">
                                            <?php echo htmlspecialchars($row['category_name'] ?? 'Umum'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-weight: 600; color: var(--dark);">
                                            Rp <?php echo number_format($row['price'], 0, ',', '.'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-weight: 700;"><?php echo $row['stock']; ?></span> pcs
                                    </td>
                                    <td>
                                        <?php if ($row['stock'] == 0): ?>
                                            <span class="badge badge-danger"><i class="fa-solid fa-circle-xmark"></i> Habis</span>
                                        <?php elseif ($row['stock'] < 10): ?>
                                            <span class="badge badge-warning"><i class="fa-solid fa-triangle-exclamation"></i> Menipis (&lt;10)</span>
                                        <?php else: ?>
                                            <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> Siap Jual</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="display: inline-flex; gap: 0.35rem;">
                                            <a href="edit_product.php?id=<?php echo $row['products_id']; ?>" class="btn btn-outline btn-sm" title="Edit Data">
                                                <i class="fa-regular fa-pen-to-square"></i> Edit
                                            </a>
                                            <a href="delete_product.php?id=<?php echo $row['products_id']; ?>" 
                                               class="btn btn-danger btn-sm" 
                                               title="Hapus Produk"
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus produk \'<?php echo addslashes($row['name']); ?>\'? Tindakan ini tidak dapat dibatalkan.');">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr id="emptyRow">
                                <td colspan="7" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                    <i class="fa-solid fa-box-open" style="font-size: 2.5rem; margin-bottom: 0.75rem; display: block; opacity: 0.5;"></i>
                                    Tidak ada produk yang ditemukan. Silakan tambahkan produk baru.
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

    <script>
        // Live client-side search filter
        const searchInput = document.getElementById('searchInput');
        const table = document.getElementById('productsTable');
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

        searchInput.addEventListener('keyup', function () {
            const filter = searchInput.value.toLowerCase();
            let visibleCount = 0;

            for (let i = 0; i < rows.length; i++) {
                if (rows[i].id === 'emptyRow') continue;
                const text = rows[i].textContent || rows[i].innerText;
                if (text.toLowerCase().indexOf(filter) > -1) {
                    rows[i].style.display = '';
                    visibleCount++;
                } else {
                    rows[i].style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>
