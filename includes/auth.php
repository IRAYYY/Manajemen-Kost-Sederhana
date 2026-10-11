<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn()
{
    return isset($_SESSION['user']);
}

function currentUser()
{
    return $_SESSION['user'] ?? null;
}

function refreshCurrentUser($pdo)
{
    if (!isLoggedIn()) {
        return null;
    }

    $userId = $_SESSION['user']['id'];
    $stmt = $pdo->prepare(
        "SELECT id, name, email, phone, role, status, verification_status, ktp_file, address, verification_notes 
         FROM users 
         WHERE id = ? 
         LIMIT 1"
    );
    $stmt->execute([$userId]);
    $fresh = $stmt->fetch();
    if ($fresh) {
        $_SESSION['user'] = $fresh;
        return $fresh;
    }

    return $_SESSION['user'];
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: /kost-management/public/login.php');
        exit;
    }
}

function requireAdmin()
{
    requireLogin();

    if (($_SESSION['user']['role'] ?? null) !== 'admin') {
        http_response_code(403);
        exit('Akses ditolak.');
    }
}

function requireTenant()
{
    requireLogin();

    if (($_SESSION['user']['role'] ?? null) !== 'tenant') {
        http_response_code(403);
        exit('Akses ditolak.');
    }
}

function redirectByRole()
{
    if (!isLoggedIn()) {
        header('Location: /kost-management/public/login.php');
        exit;
    }

    if ($_SESSION['user']['role'] === 'admin') {
        header('Location: /kost-management/admin/dashboard.php');
        exit;
    }

    header('Location: /kost-management/tenant/dashboard.php');
    exit;
}