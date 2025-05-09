<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkAdminSession() {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: admin_login.php");
        exit();
    }
}

function getAdminName() {
    return $_SESSION['admin_name'] ?? 'Admin User';
}

function getAdminImage() {
    return $_SESSION['admin_image'] ?? 'https://randomuser.me/api/portraits/women/44.jpg';
}

function getAdminEmail() {
    return $_SESSION['admin_email'] ?? '';
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
} 