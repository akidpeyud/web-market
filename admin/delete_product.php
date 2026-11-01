<?php
session_start();
include '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Gunakan prepared statement
    $stmt = $conn->prepare("DELETE FROM products WHERE products_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header('Location: products.php?status=deleted');
        exit;
    } else {
        header('Location: products.php?status=error');
        exit;
    }
} else {
    header('Location: products.php');
    exit;
}
?>
