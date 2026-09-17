<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/kost-management/public/login.php');
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    redirect(
        '/kost-management/public/login.php?error=Email dan password wajib diisi.'
    );
}

/*
|--------------------------------------------------------------------------
| Cari user
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        id,
        name,
        email,
        phone,
        password,
        role,
        status
     FROM users
     WHERE email = ?
     LIMIT 1"
);

$stmt->execute([$email]);

$user = $stmt->fetch();

if (!$user) {
    redirect(
        '/kost-management/public/login.php?error=Email atau password salah.'
    );
}

/*
|--------------------------------------------------------------------------
| Cek status akun
|--------------------------------------------------------------------------
*/

if ($user['status'] !== 'active') {
    redirect(
        '/kost-management/public/login.php?error=Akun Anda tidak aktif.'
    );
}

/*
|--------------------------------------------------------------------------
| Verifikasi password
|--------------------------------------------------------------------------
*/

if (!password_verify($password, $user['password'])) {
    redirect(
        '/kost-management/public/login.php?error=Email atau password salah.'
    );
}

/*
|--------------------------------------------------------------------------
| Regenerate session ID
|--------------------------------------------------------------------------
*/

session_regenerate_id(true);

/*
|--------------------------------------------------------------------------
| Simpan user ke session
|--------------------------------------------------------------------------
*/

$_SESSION['user'] = [
    'id' => $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'phone' => $user['phone'],
    'role' => $user['role'],
    'status' => $user['status'],
];

/*
|--------------------------------------------------------------------------
| Redirect berdasarkan role
|--------------------------------------------------------------------------
*/

redirectByRole();