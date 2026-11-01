<?php
session_start();
include '../config.php';

// Cek role pengguna
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'kasir') {
    header('Location: ../login.php');
    exit;
}

// Inisialisasi keranjang
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$alertMessage = '';
$alertType = '';

// Generate kode transaksi berikutnya
$sqlCode = "SELECT MAX(transaction_code) AS max_code FROM transactions";
$resCode = $conn->query($sqlCode);
$rowCode = $resCode->fetch_assoc();
$last_code = $rowCode['max_code'] ?? null;

if ($last_code) {
    // Ambil angka dari kode transaksi terakhir
    $num = intval(substr($last_code, 3)) + 1;
    $next_code = 'TRX' . sprintf('%04d', $num);
} else {
    $next_code = 'TRX0001';
}

// 1. Tambah ke keranjang
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $products_id = intval($_POST['products_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $buyer_name = trim($_POST['buyer_name'] ?? '');

    if (!empty($buyer_name)) {
        $_SESSION['buyer_name'] = $buyer_name;
    }

    if ($products_id > 0 && $quantity > 0) {
        $stmt = $conn->prepare("SELECT products_id, name, price, stock FROM products WHERE products_id = ?");
        $stmt->bind_param("i", $products_id);
        $stmt->execute();
        $pRes = $stmt->get_result();
        
        if ($pRes && $pRes->num_rows > 0) {
            $product = $pRes->fetch_assoc();
            
            // Hitung berapa unit produk ini yang sudah ada di cart saat ini
            $existingQty = 0;
            $foundIndex = -1;
            foreach ($_SESSION['cart'] as $idx => $item) {
                if ($item['products_id'] == $products_id) {
                    $existingQty = $item['quantity'];
                    $foundIndex = $idx;
                    break;
                }
            }

            if (($existingQty + $quantity) > $product['stock']) {
                $alertMessage = "Stok produk '" . htmlspecialchars($product['name']) . "' tidak mencukupi (Tersedia: " . $product['stock'] . " pcs, di keranjang: " . $existingQty . " pcs).";
                $alertType = 'danger';
            } else {
                if ($foundIndex >= 0) {
                    $_SESSION['cart'][$foundIndex]['quantity'] += $quantity;
                } else {
                    $_SESSION['cart'][] = [
                        'products_id' => $product['products_id'],
                        'name' => $product['name'],
                        'price' => floatval($product['price']),
                        'quantity' => $quantity
                    ];
                }
                $alertMessage = "Berhasil menambahkan '" . htmlspecialchars($product['name']) . "' ke keranjang belanja.";
                $alertType = 'success';
            }
        }
    } else {
        $alertMessage = "Jumlah barang yang dibeli minimal 1 pcs.";
        $alertType = 'danger';
    }
}

// 2. Hapus item dari keranjang
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_item'])) {
    $index = intval($_POST['index'] ?? -1);
    if (isset($_SESSION['cart'][$index])) {
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        $alertMessage = "Item telah dihapus dari keranjang.";
        $alertType = 'info';
    }
}

// 3. Reset / Kosongkan keranjang
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['clear_cart'])) {
    $_SESSION['cart'] = [];
    $alertMessage = "Keranjang belanja telah dikosongkan.";
    $alertType = 'info';
}

// 4. Proses Checkout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkout'])) {
    $buyer_name = trim($_POST['buyer_name_checkout'] ?? ($_SESSION['buyer_name'] ?? 'Umum'));
    if (empty($buyer_name)) {
        $buyer_name = 'Pelanggan Umum';
    }

    if (empty($_SESSION['cart'])) {
        $alertMessage = "Keranjang belanja masih kosong! Tambahkan produk terlebih dahulu.";
        $alertType = 'danger';
    } else {
        $transaction_code = $next_code;
        $conn->begin_transaction();

        try {
            foreach ($_SESSION['cart'] as $item) {
                $pId = $item['products_id'];
                $qty = $item['quantity'];
                $price = $item['price'];
                $subtotal = $price * $qty;

                // Kurangi stok
                $stmtStock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE products_id = ? AND stock >= ?");
                $stmtStock->bind_param("iii", $qty, $pId, $qty);
                $stmtStock->execute();

                if ($stmtStock->affected_rows <= 0) {
                    throw new Exception("Stok untuk produk '" . $item['name'] . "' tidak mencukupi untuk diproses.");
                }

                // Insert transaksi
                $stmtTrx = $conn->prepare("INSERT INTO transactions (transaction_code, buyer_name, products_id, quantity, price, total) VALUES (?, ?, ?, ?, ?, ?)");
                $stmtTrx->bind_param("ssiidd", $transaction_code, $buyer_name, $pId, $qty, $price, $subtotal);
                $stmtTrx->execute();
            }

            $conn->commit();

            // Reset cart
            $_SESSION['cart'] = [];
            unset($_SESSION['buyer_name']);

            // Redirect ke invoice
            header("Location: invoice.php?transaction_code=" . urlencode($transaction_code));
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $alertMessage = "Transaksi gagal diproses: " . $e->getMessage();
            $alertType = 'danger';
        }
    }
}

// Ambil daftar produk dengan stok > 0
$productsQuery = $conn->query("
    SELECT p.*, c.category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE p.stock > 0 
    ORDER BY p.name ASC
");

// Hitung total belanja saat ini
$grandTotal = 0;
$totalItemCount = 0;
foreach ($_SESSION['cart'] as $item) {
    $grandTotal += ($item['price'] * $item['quantity']);
    $totalItemCount += $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Kasir - WebMarket POS</title>
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
                <h1>Mesin Kasir & Penjualan</h1>
                <p>Kelola pesanan pelanggan toko secara langsung dan cetak struk pembayaran.</p>
            </div>
            <div class="page-header-actions">
                <span class="badge badge-primary" style="font-size: 0.95rem; padding: 0.6rem 1.1rem;">
                    <i class="fa-solid fa-receipt"></i> Nota: <strong><?php echo $next_code; ?></strong>
                </span>
            </div>
        </div>

        <?php if (!empty($alertMessage)): ?>
            <div class="alert alert-<?php echo $alertType; ?>">
                <i class="fa-solid <?php echo $alertType == 'success' ? 'fa-circle-check' : ($alertType == 'info' ? 'fa-circle-info' : 'fa-circle-exclamation'); ?>"></i>
                <span><?php echo $alertMessage; ?></span>
            </div>
        <?php endif; ?>

        <div class="pos-layout">
            <!-- Kolom Kiri: Input Pembeli & Pilih Produk -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fa-solid fa-cart-plus" style="color: #10b981;"></i>
                        Input Barang Belanjaan
                    </div>
                </div>

                <form method="POST" action="" id="addForm">
                    <div class="form-group">
                        <label class="form-label" for="buyer_name">Nama Pelanggan / Pembeli <span style="color: var(--danger);">*</span></label>
                        <div class="input-group">
                            <span class="input-icon-left"><i class="fa-solid fa-user"></i></span>
                            <input type="text" id="buyer_name" name="buyer_name" class="form-control input-with-icon-left" placeholder="Contoh: Bpk. Ahmad / Pelanggan Umum" required value="<?php echo htmlspecialchars($_SESSION['buyer_name'] ?? 'Pelanggan Umum'); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="products_id">Pilih Produk Barang <span style="color: var(--danger);">*</span></label>
                        <select id="products_id" name="products_id" class="form-control" required onchange="updateProductHint()">
                            <option value="">-- Pilih Barang dari Katalog --</option>
                            <?php while ($prod = $productsQuery->fetch_assoc()): ?>
                                <option value="<?php echo $prod['products_id']; ?>" 
                                        data-price="<?php echo $prod['price']; ?>" 
                                        data-stock="<?php echo $prod['stock']; ?>"
                                        data-cat="<?php echo htmlspecialchars($prod['category_name'] ?? 'Umum'); ?>">
                                    <?php echo htmlspecialchars($prod['name']); ?> &bull; (Stok: <?php echo $prod['stock']; ?>) - Rp <?php echo number_format($prod['price'], 0, ',', '.'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Selected Product Info Box -->
                    <div id="productInfoBox" style="display: none; background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem;">
                            <span>Harga Satuan: <strong id="hintPrice" style="color: var(--primary);">-</strong></span>
                            <span>Sisa Stok: <strong id="hintStock" style="color: #10b981;">-</strong></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="quantity">Jumlah Qty (pcs) <span style="color: var(--danger);">*</span></label>
                        <div class="input-group">
                            <span class="input-icon-left"><i class="fa-solid fa-calculator"></i></span>
                            <input type="number" id="quantity" name="quantity" class="form-control input-with-icon-left" value="1" min="1" required>
                        </div>
                    </div>

                    <button type="submit" name="add_to_cart" class="btn btn-success btn-block btn-lg" style="margin-top: 1.5rem;">
                        <i class="fa-solid fa-plus"></i> Tambah ke Keranjang
                    </button>
                </form>
            </div>

            <!-- Kolom Kanan: Keranjang Belanja & Selesaikan Pembayaran -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fa-solid fa-basket-shopping" style="color: var(--primary);"></i>
                            Keranjang Belanja
                            <span class="badge badge-neutral"><?php echo $totalItemCount; ?> item</span>
                        </div>
                        <?php if (!empty($_SESSION['cart'])): ?>
                            <form method="POST" action="" onsubmit="return confirm('Kosongkan semua item di keranjang?');">
                                <button type="submit" name="clear_cart" class="btn btn-outline btn-sm" style="color: var(--danger); border-color: var(--danger-border);">
                                    <i class="fa-solid fa-trash-can"></i> Kosongkan
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Harga</th>
                                    <th style="text-align: center;">Qty</th>
                                    <th>Subtotal</th>
                                    <th style="text-align: right;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($_SESSION['cart'])): ?>
                                    <?php foreach ($_SESSION['cart'] as $idx => $cItem): ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight: 700; color: var(--dark);"><?php echo htmlspecialchars($cItem['name']); ?></div>
                                            </td>
                                            <td>Rp <?php echo number_format($cItem['price'], 0, ',', '.'); ?></td>
                                            <td style="text-align: center;">
                                                <span class="badge badge-neutral" style="font-weight: 700;"><?php echo $cItem['quantity']; ?></span>
                                            </td>
                                            <td>
                                                <strong>Rp <?php echo number_format($cItem['price'] * $cItem['quantity'], 0, ',', '.'); ?></strong>
                                            </td>
                                            <td style="text-align: right;">
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="index" value="<?php echo $idx; ?>">
                                                    <button type="submit" name="hapus_item" class="btn btn-outline btn-sm" style="color: var(--danger);" title="Hapus item">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                                            <i class="fa-solid fa-cart-arrow-down" style="font-size: 2rem; margin-bottom: 0.5rem; display: block; opacity: 0.4;"></i>
                                            Keranjang masih kosong. Pilih produk di sebelah kiri untuk menambahkan barang.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Summary & Checkout Section -->
                    <div class="pos-cart-summary">
                        <div class="summary-row">
                            <span style="color: var(--text-muted);">Total Jumlah Barang:</span>
                            <span style="font-weight: 600;"><?php echo $totalItemCount; ?> pcs</span>
                        </div>
                        <div class="summary-row grand-total">
                            <span>TOTAL BAYAR:</span>
                            <span>Rp <?php echo number_format($grandTotal, 0, ',', '.'); ?></span>
                        </div>

                        <!-- Real-time Cash / Change Calculator for Cashier -->
                        <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                            <div class="form-group">
                                <label class="form-label" for="cashAmount">Uang Diterima dari Pelanggan (Rp):</label>
                                <div class="input-group">
                                    <span class="input-icon-left" style="font-weight: 700; font-size: 0.85rem;">Rp</span>
                                    <input type="number" id="cashAmount" class="form-control input-with-icon-left" placeholder="Contoh: 100000" oninput="calculateChange()">
                                </div>
                            </div>

                            <div class="summary-row" style="background: #ffffff; padding: 0.75rem 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); font-weight: 700;">
                                <span>Kembalian:</span>
                                <span id="changeDisplay" style="color: var(--text-main); font-size: 1.15rem;">Rp 0</span>
                            </div>
                        </div>

                        <form method="POST" action="" style="margin-top: 1.25rem;">
                            <input type="hidden" name="buyer_name_checkout" id="buyer_name_checkout">
                            <button type="submit" name="checkout" class="btn btn-primary btn-block btn-lg" <?php echo empty($_SESSION['cart']) ? 'disabled style="opacity: 0.6; cursor: not-allowed;"' : ''; ?> onclick="syncBuyerName()">
                                <i class="fa-solid fa-check-to-slot"></i> Selesaikan Transaksi & Cetak
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="app-footer">
        &copy; <?php echo date('Y'); ?> WebMarket POS System. All rights reserved.
    </footer>

    <script>
        const grandTotal = <?php echo $grandTotal; ?>;

        function updateProductHint() {
            const select = document.getElementById('products_id');
            const box = document.getElementById('productInfoBox');
            const selected = select.options[select.selectedIndex];

            if (selected && selected.value) {
                const price = parseFloat(selected.getAttribute('data-price') || 0);
                const stock = selected.getAttribute('data-stock') || 0;
                document.getElementById('hintPrice').textContent = 'Rp ' + price.toLocaleString('id-ID');
                document.getElementById('hintStock').textContent = stock + ' pcs';
                box.style.display = 'block';
            } else {
                box.style.display = 'none';
            }
        }

        function calculateChange() {
            const cash = parseFloat(document.getElementById('cashAmount').value) || 0;
            const changeDisplay = document.getElementById('changeDisplay');
            const change = cash - grandTotal;

            if (cash >= grandTotal) {
                changeDisplay.textContent = 'Rp ' + change.toLocaleString('id-ID');
                changeDisplay.style.color = '#10b981';
            } else if (cash > 0) {
                changeDisplay.textContent = 'Kurang Rp ' + Math.abs(change).toLocaleString('id-ID');
                changeDisplay.style.color = '#ef4444';
            } else {
                changeDisplay.textContent = 'Rp 0';
                changeDisplay.style.color = 'var(--text-main)';
            }
        }

        function syncBuyerName() {
            const buyerInput = document.getElementById('buyer_name');
            document.getElementById('buyer_name_checkout').value = buyerInput ? buyerInput.value : '';
        }
    </script>
</body>
</html>
