<?php
session_start();
include 'config.php';

// Jika pengguna sudah login, arahkan ke dashboard
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin/index.php');
        exit;
    } elseif ($_SESSION['role'] == 'kasir') {
        header('Location: kasir/index.php');
        exit;
    }
}

$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        // Query user dengan prepared statement atau sanitasi aman
        $stmt = $conn->prepare("SELECT id, username, password, name, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $authenticated = false;

            // Cek hash modern terlebih dahulu
            if (password_verify($password, $user['password'])) {
                $authenticated = true;
            } 
            // Fallback ke hash MD5 lama jika cocok
            elseif ($user['password'] === md5($password)) {
                $authenticated = true;
                // Upgrade otomatis password ke hash bcrypt yang aman
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->bind_param("si", $newHash, $user['id']);
                $updateStmt->execute();
            }

            if ($authenticated) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] == 'admin') {
                    header('Location: admin/index.php');
                    exit;
                } else {
                    header('Location: kasir/index.php');
                    exit;
                }
            } else {
                $errorMessage = "Password yang Anda masukkan salah.";
            }
        } else {
            $errorMessage = "Username tidak terdaftar.";
        }
    } else {
        $errorMessage = "Harap isi username dan password.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - WebMarket POS</title>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Main Style -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-brand-icon">
                    <i class="fa-solid fa-shop"></i>
                </div>
                <h1 class="auth-title">WebMarket POS</h1>
                <p class="auth-subtitle">Sistem Kasir & Manajemen Toko Modern</p>
            </div>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($errorMessage); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <div class="input-group">
                        <span class="input-icon-left"><i class="fa-regular fa-user"></i></span>
                        <input type="text" id="username" name="username" class="form-control input-with-icon-left" placeholder="Masukkan username" required autofocus value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group">
                        <span class="input-icon-left"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" id="password" name="password" class="form-control input-with-icon-left" placeholder="Masukkan password" required>
                        <button type="button" class="input-icon-right" id="togglePassword" aria-label="Toggle password visibility">
                            <i class="fa-regular fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1.5rem;">
                    <i class="fa-solid fa-right-to-bracket"></i> Masuk Sekarang
                </button>
            </form>

            <div class="demo-credentials">
                <p><i class="fa-solid fa-key"></i> Quick Login (Akses Demo):</p>
                <div class="demo-pills">
                    <button type="button" class="demo-pill" onclick="fillCredentials('admin', 'admin')">
                        <i class="fa-solid fa-user-shield"></i> Admin
                    </button>
                    <button type="button" class="demo-pill" onclick="fillCredentials('kasir', 'kasir')">
                        <i class="fa-solid fa-cash-register"></i> Kasir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        toggleBtn.addEventListener('click', function () {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            eyeIcon.classList.toggle('fa-eye');
            eyeIcon.classList.toggle('fa-eye-slash');
        });

        // Quick login credentials helper
        function fillCredentials(user, pass) {
            document.getElementById('username').value = user;
            document.getElementById('password').value = pass;
            document.getElementById('password').focus();
        }
    </script>
</body>
</html>
