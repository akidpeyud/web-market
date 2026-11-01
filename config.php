<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$servername = "localhost";
$username = "yayasa41_ti_kelompok5_user";
$password = "UIHD8282edh37";
$dbname = "yayasa41_ti_kelompok5";

// Create connection (with fallback for local XAMPP environment)
$conn = @new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    $conn = new mysqli($servername, "root", "", $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Assuming you have a users table and sessions are set during login
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $sql = "SELECT id, name, role FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
    }
}
?>
