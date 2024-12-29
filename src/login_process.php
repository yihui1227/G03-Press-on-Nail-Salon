<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// 帳碼
$correct_username = 'admin';
$correct_password = 'admin';

$username = $_POST['username'];
$password = $_POST['password'];

if ($username === $correct_username && $password === $correct_password) {
    $_SESSION['admin_logged_in'] = true;
    header('Location: ../src/dashboard.php');
    exit();
} else {
    header('Location: ../assets/login.html?error=1');
    exit();
}
?>