<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

// 帳碼
$correct_username = 'admin';
$correct_password = 'admin';

$username = $_POST['username'];
$password = $_POST['password'];

if ($username === $correct_username && $password === $correct_password) {
    header('Location: ../dashboard.php');
    exit();
} else {
    header('Location: ../assets/login.html?error=1');
    exit();
}
?>