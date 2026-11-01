<?php
session_start();
include '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'kasir') {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? 'kasir';
$name = $_SESSION['name'] ?? 'Kasir';

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Ambil data user dari database
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $isCurrentValid = false;
    if ($user) {
        if (password_verify($currentPassword, $user['password'])) {
            $isCurrentValid = true;
        } elseif ($user['password'] === md5($currentPassword)) {
            $isCurrentValid = true;
        }
    }

    if ($isCurrentValid) {
        if (strlen($newPassword) < 5) {
            $errorMessage = "Password baru minimal terdiri dari 5 karakter.";
        } elseif ($newPassword === $confirmPassword) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->bind_param("si", $hashedPassword, $userId);

            if ($updateStmt->execute()) {
                $successMessage = "Kata sandi kasir berhasil diperbarui!";
            } else {
                $errorMessage = "Terjadi kesalahan sistem saat memperbarui password: " . $conn->error;
            }
        } else {
            $errorMessage = "Konfirmasi kata sandi baru tidak sesuai.";
        }
    } else {
        $errorMessage = "Kata sandi saat ini yang Anda masukkan salah.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontrol Akun Kasir - WebMarket</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Main Style -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="main-wrapper" style="max-width: 680px;">
        <div class="page-header">
            <div class="page-header-text">
                <h1>Kontrol Akun & Keamanan Kasir</h1>
                <p>Kelola kata sandi akun kasir Anda untuk menjaga integritas data transaksi.</p>
            </div>
            <div class="page-header-actions">
                <a href="index.php" class="btn btn-outline">
                    <i class="fa-solid fa-arrow-left"></i> Beranda Kasir
                </a>
            </div>
        </div>

        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo htmlspecialchars($successMessage); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($errorMessage); ?></span>
            </div>
        <?php endif; ?>

        <!-- Kasir Badge Card -->
        <div class="card" style="margin-bottom: 1.5rem; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div class="user-avatar" style="width: 48px; height: 48px; font-size: 1.25rem; background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);">
                    <?php echo strtoupper(substr($name, 0, 1)); ?>
                </div>
                <div>
                    <h3 style="font-size: 1.1rem; color: var(--dark); font-weight: 700;"><?php echo htmlspecialchars($name); ?></h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem;">
                        Username: <strong>@<?php echo htmlspecialchars($username); ?></strong> &bull; Peran: <span class="badge badge-success">Kasir POS</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fa-solid fa-key" style="color: var(--primary);"></i>
                    Ganti Kata Sandi
                </div>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="current_password">Kata Sandi Saat Ini <span style="color: var(--danger);">*</span></label>
                    <div class="input-group">
                        <span class="input-icon-left"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" id="current_password" name="current_password" class="form-control input-with-icon-left" placeholder="Masukkan kata sandi lama Anda" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="new_password">Kata Sandi Baru <span style="color: var(--danger);">*</span></label>
                    <div class="input-group">
                        <span class="input-icon-left"><i class="fa-solid fa-shield-halved"></i></span>
                        <input type="password" id="new_password" name="new_password" class="form-control input-with-icon-left" placeholder="Minimal 5 karakter" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Konfirmasi Kata Sandi Baru <span style="color: var(--danger);">*</span></label>
                    <div class="input-group">
                        <span class="input-icon-left"><i class="fa-solid fa-check-double"></i></span>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control input-with-icon-left" placeholder="Ulangi kata sandi baru" required>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem; justify-content: flex-end;">
                    <a href="index.php" class="btn btn-outline">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Password Baru
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
