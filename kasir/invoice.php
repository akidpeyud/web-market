<?php
session_start();
include '../config.php';

// Cek role pengguna
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'kasir') {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['transaction_code']) || empty($_GET['transaction_code'])) {
    header('Location: transactions.php');
    exit;
}

$transaction_code = trim($_GET['transaction_code']);

// Query detail transaksi
$stmt = $conn->prepare("
    SELECT t.transaction_code, t.buyer_name, t.quantity, t.price, t.total, t.created_at, p.name as product_name
    FROM transactions t
    JOIN products p ON t.products_id = p.products_id
    WHERE t.transaction_code = ?
    ORDER BY t.id ASC
");
$stmt->bind_param("s", $transaction_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $items = $result->fetch_all(MYSQLI_ASSOC);
} else {
    echo "Transaksi dengan kode " . htmlspecialchars($transaction_code) . " tidak ditemukan.";
    exit;
}

// Hitung total keseluruhan & total kuantitas
$totalKeseluruhan = 0;
$totalQty = 0;
foreach ($items as $it) {
    $totalKeseluruhan += $it['total'];
    $totalQty += $it['quantity'];
}

$firstItem = $items[0];
$buyerName = $firstItem['buyer_name'];
$trxDate = !empty($firstItem['created_at']) ? date('d/m/Y H:i', strtotime($firstItem['created_at'])) : date('d/m/Y H:i');
$kasirName = $_SESSION['name'] ?? 'Kasir';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Transaksi #<?php echo htmlspecialchars($transaction_code); ?> - WebMarket</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Main Style -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .receipt-container {
            max-width: 540px;
            margin: 2rem auto;
            background: #ffffff;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border-color);
            padding: 2.5rem 2rem;
            position: relative;
        }

        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #cbd5e1;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .receipt-brand-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 0.75rem;
        }

        .receipt-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--dark);
            letter-spacing: -0.01em;
        }

        .receipt-subtitle {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.15rem;
        }

        .receipt-meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            background: #f8fafc;
            padding: 1rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-subtle);
        }

        .receipt-meta-grid div span.label {
            display: block;
            font-size: 0.72rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 700;
        }

        .receipt-meta-grid div span.val {
            font-weight: 700;
            color: var(--dark);
        }

        .receipt-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
            font-size: 0.88rem;
        }

        .receipt-items-table th {
            text-align: left;
            padding: 0.6rem 0;
            border-bottom: 2px solid var(--border-color);
            color: var(--text-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .receipt-items-table td {
            padding: 0.75rem 0;
            border-bottom: 1px dashed var(--border-subtle);
            color: var(--text-main);
        }

        .receipt-total-section {
            border-top: 2px dashed #cbd5e1;
            padding-top: 1rem;
            margin-bottom: 1.5rem;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.95rem;
            padding: 0.35rem 0;
        }

        .total-row.grand {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--dark);
            margin-top: 0.5rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--border-color);
        }

        .receipt-footer-note {
            text-align: center;
            font-size: 0.78rem;
            color: var(--text-subtle);
            margin-top: 1.5rem;
            line-height: 1.4;
        }

        .receipt-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .top-nav, .receipt-actions, .app-footer, .no-print {
                display: none !important;
            }
            .receipt-container {
                box-shadow: none !important;
                border: none !important;
                margin: 0 auto !important;
                padding: 1rem 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <?php include 'navbar.php'; ?>
    </div>

    <main class="main-wrapper" style="margin-top: 1rem;">
        <div class="receipt-container">
            <div class="receipt-header">
                <div class="receipt-brand-icon">
                    <i class="fa-solid fa-store"></i>
                </div>
                <h2 class="receipt-title">WEBMARKET POS</h2>
                <p class="receipt-subtitle">Struk Bukti Pembayaran Resmi</p>
                <div style="font-size: 0.75rem; color: var(--text-subtle); margin-top: 0.25rem;">
                    Jl. Pasar Induk Modern No. 45 &bull; Telp. (021) 8899-7700
                </div>
            </div>

            <div class="receipt-meta-grid">
                <div>
                    <span class="label">No. Transaksi</span>
                    <span class="val"><?php echo htmlspecialchars($transaction_code); ?></span>
                </div>
                <div style="text-align: right;">
                    <span class="label">Tanggal & Waktu</span>
                    <span class="val"><?php echo htmlspecialchars($trxDate); ?></span>
                </div>
                <div>
                    <span class="label">Nama Pembeli</span>
                    <span class="val"><?php echo htmlspecialchars($buyerName); ?></span>
                </div>
                <div style="text-align: right;">
                    <span class="label">Kasir Bertugas</span>
                    <span class="val"><?php echo htmlspecialchars($kasirName); ?></span>
                </div>
            </div>

            <table class="receipt-items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Harga</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                            </td>
                            <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                            <td style="text-align: right;">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                            <td style="text-align: right; font-weight: 700;">Rp <?php echo number_format($item['total'], 0, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="receipt-total-section">
                <div class="total-row">
                    <span style="color: var(--text-muted);">Total Kuantitas:</span>
                    <span><?php echo $totalQty; ?> pcs</span>
                </div>
                <div class="total-row grand">
                    <span>TOTAL TAGIHAN:</span>
                    <span style="color: #10b981;">Rp <?php echo number_format($totalKeseluruhan, 0, ',', '.'); ?></span>
                </div>
            </div>

            <div class="receipt-footer-note">
                <p>*** TERIMA KASIH ATAS KUNJUNGAN ANDA ***</p>
                <p>Barang yang sudah dibeli tidak dapat dikembalikan tanpa nota resmi.</p>
            </div>

            <div class="receipt-actions no-print">
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="fa-solid fa-print"></i> Cetak Struk
                </button>
                <a href="transactions.php" class="btn btn-success">
                    <i class="fa-solid fa-cart-plus"></i> Transaksi Baru
                </a>
                <a href="index.php" class="btn btn-outline">
                    <i class="fa-solid fa-house"></i> Beranda
                </a>
            </div>
        </div>
    </main>

    <footer class="app-footer no-print">
        &copy; <?php echo date('Y'); ?> WebMarket POS System. All rights reserved.
    </footer>
</body>
</html>
