<?php
session_start();
include '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: products.php');
    exit;
}

// Ambil data produk
$stmt = $conn->prepare("SELECT * FROM products WHERE products_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: products.php?status=error');
    exit;
}

$product = $result->fetch_assoc();
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);

    if (!empty($name) && $price >= 0 && $stock >= 0) {
        $updateStmt = $conn->prepare("UPDATE products SET name = ?, price = ?, stock = ?, category_id = ? WHERE products_id = ?");
        $updateStmt->bind_param("sdiii", $name, $price, $stock, $category_id, $id);

        if ($updateStmt->execute()) {
            header('Location: products.php?status=updated');
            exit;
        } else {
            $errorMessage = "Gagal memperbarui produk: " . $conn->error;
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
    <title>Edit Produk - WebMarket</title>
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
                <h1>Edit Data Produk</h1>
                <p>Perbarui rincian informasi barang: #<?php echo $product['products_id']; ?> - <?php echo htmlspecialchars($product['name']); ?></p>
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
                    <i class="fa-regular fa-pen-to-square" style="color: var(--primary);"></i>
                    Formulir Pembaruan
                </div>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="name">Nama Produk / Barang <span style="color: var(--danger);">*</span></label>
                    <div class="input-group">
                        <span class="input-icon-left"><i class="fa-solid fa-tag"></i></span>
                        <input type="text" id="name" name="name" class="form-control input-with-icon-left" required value="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" for="price">Harga Jual Satuan (Rp) <span style="color: var(--danger);">*</span></label>
                        <div class="input-group">
                            <span class="input-icon-left" style="font-weight: 700; font-size: 0.85rem;">Rp</span>
                            <input type="number" id="price" name="price" step="100" min="0" class="form-control input-with-icon-left" required value="<?php echo htmlspecialchars($product['price']); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="stock">Jumlah Stok <span style="color: var(--danger);">*</span></label>
                        <div class="input-group">
                            <span class="input-icon-left"><i class="fa-solid fa-layer-group"></i></span>
                            <input type="number" id="stock" name="stock" min="0" class="form-control input-with-icon-left" required value="<?php echo htmlspecialchars($product['stock']); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="category_id">Kategori Produk <span style="color: var(--danger);">*</span></label>
                    <select id="category_id" name="category_id" class="form-control" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php while ($cat = $catQuery->fetch_assoc()): ?>
                            <option value="<?php echo $cat['category_id']; ?>" <?php echo ($product['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem; justify-content: flex-end;">
                    <a href="products.php" class="btn btn-outline">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-check"></i> Simpan Perubahan
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
