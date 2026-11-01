<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Kasir</title>
    <script src="script.js" defer></script>
</head>
<body>
    <div class="kasir-container">
        <h2>Selamat datang di sistem kasir</h2>
        <button onclick="logout()">Logout</button>
    </div>
</body>
</html>
