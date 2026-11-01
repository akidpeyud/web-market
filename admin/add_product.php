<?php
session_start();
include '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);

    if (!empty($name) && $price >= 0 && $stock >= 0 && $category_id > 0) {
        $stmt = $conn->prepare("INSERT INTO products (name, price, stock, category_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdii", $name, $price, $stock, $category_id);
        
        if ($stmt->execute()) {
            header('Location: products.php?status=added');
            exit;
        } else {
            $errorMessage = "Gagal menyimpan produk: " . $conn->error;
        }
    } else {
        $errorMessage = "Mohon lengkapi seluruh kolom formulir dengan data yang valid.";
    }
}

// Ambil daftar kategori
$catQuery = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk Baru - WebMarket</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Main Style -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="main-wrapper" style="max-width: 760px;">
        <div class="page-header">
            <div class="page-header-text">
                <h1>Tambah Produk Baru</h1>
                <p>Daftarkan barang dagangan baru ke dalam sistem inventaris WebMarket.</p>
            </div>
            <div class="page-header-actions">
                <a href="products.php" class="btn btn-outline">
                    <i class="fa-solid fa-arrow-left"></i> Kembali ke Katalog
                </a>
            </div>
        </div>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($errorMessage); ?></span>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fa-solid fa-circle-plus" style="color: var(--primary);"></i>
                    Informasi Barang
                </div>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="name">Nama Produk / Barang <span style="color: var(--danger);">*</span></label>
                    <div class="input-group">
                        <span class="input-icon-left"><i class="fa-solid fa-tag"></i></span>
                        <input type="text" id="name" name="name" class="form-control input-with-icon-left" placeholder="Contoh: Minyak Goreng 2L, Beras Premium" required autofocus value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" for="price">Harga Jual Satuan (Rp) <span style="color: var(--danger);">*</span></label>
                        <div class="input-group">
                            <span class="input-icon-left" style="font-weight: 700; font-size: 0.85rem;">Rp</span>
                            <input type="number" id="price" name="price" step="100" min="0" class="form-control input-with-icon-left" placeholder="Contoh: 25000" required value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="stock">Jumlah Stok Awal <span style="color: var(--danger);">*</span></label>
                        <div class="input-group">
                            <span class="input-icon-left"><i class="fa-solid fa-layer-group"></i></span>
                            <input type="number" id="stock" name="stock" min="0" class="form-control input-with-icon-left" placeholder="Contoh: 50" required value="<?php echo isset($_POST['stock']) ? htmlspecialchars($_POST['stock']) : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="category_id">Kategori Produk <span style="color: var(--danger);">*</span></label>
                    <select id="category_id" name="category_id" class="form-control" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php while ($cat = $catQuery->fetch_assoc()): ?>
                            <option value="<?php echo $cat['category_id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem; justify-content: flex-end;">
                    <a href="products.php" class="btn btn-outline">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Produk
                    </button>
                </div>
            </form>
        </div>
    </main>

    <footer class="app-footer">
        &copy; <?php echo date('Y'); ?> WebMarket POS System. All rights reserved.
    </footer>
</body>
</html>
