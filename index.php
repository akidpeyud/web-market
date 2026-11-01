<?php
session_start();

// Jika pengguna sudah login, arahkan mereka ke dashboard yang sesuai
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin/index.php');
        exit;
    } elseif ($_SESSION['role'] == 'kasir') {
        header('Location: kasir/index.php');
        exit;
    }
} else {
    // Jika belum login, arahkan ke halaman login
    header('Location: login.php');
    exit;
}
?>
