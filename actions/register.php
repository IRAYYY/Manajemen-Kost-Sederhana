
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

/*
|--------------------------------------------------------------------------
| Pastikan request menggunakan POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/kost-management/public/register.php');
}


/*
|--------------------------------------------------------------------------
| Ambil data form
|--------------------------------------------------------------------------
*/

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$passwordConfirmation = $_POST['password_confirmation'] ?? '';


/*
|--------------------------------------------------------------------------
| Validasi nama
|--------------------------------------------------------------------------
*/

if ($name === '') {
    redirect(
        '/kost-management/public/register.php?error=' .
        urlencode('Nama lengkap wajib diisi.')
    );
}

if (mb_strlen($name) < 3) {
    redirect(
        '/kost-management/public/register.php?error=' .
        urlencode('Nama lengkap minimal 3 karakter.')
    );
}

if (mb_strlen($name) > 100) {
    redirect(
        '/kost-management/public/register.php?error=' .
        urlencode('Nama lengkap maksimal 100 karakter.')
    );
}


/*
|--------------------------------------------------------------------------
| Validasi email
|--------------------------------------------------------------------------
*/

if ($email === '') {
    redirect(
        '/kost-management/public/register.php?error=' .
        urlencode('Email wajib diisi.')
    );
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect(
        '/kost-management/public/register.php?error=' .
        urlencode('Format email tidak valid.')
    );
}

if (mb_strlen($email) > 150) {
    redirect(
        '/kost-management/public/register.php?error=' .
        urlencode('Email maksimal 150 karakter.')
    );
}


/*
|--------------------------------------------------------------------------
| Validasi nomor HP
|--------------------------------------------------------------------------
*/

if ($phone !== '') {

    if (mb_strlen($phone) > 20) {
        redirect(
            '/kost-management/public/register.php?error=' .
            urlencode('Nomor HP maksimal 20 karakter.')
        );
    }

}


/*
|--------------------------------------------------------------------------
| Validasi password
|--------------------------------------------------------------------------
*/

if ($password === '') {
    redirect(
        '/kost-management/public/register.php?error=' .
        urlencode('Password wajib diisi.')
    );
}

if (strlen($password) < 8) {
    redirect(
        '/kost-management/public/register.php?error=' .
        urlencode('Password minimal 8 karakter.')
    );
}


/*
|--------------------------------------------------------------------------
| Validasi konfirmasi password
|--------------------------------------------------------------------------
*/

if ($password !== $passwordConfirmation) {
    redirect(
        '/kost-management/public/register.php?error=' .
        urlencode('Konfirmasi password tidak cocok.')
    );
}


/*
|--------------------------------------------------------------------------
| Cek apakah email sudah digunakan
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare(
        "SELECT id
         FROM users
         WHERE email = ?
         LIMIT 1"
    );

    $stmt->execute([
        $email
    ]);

    $existingUser = $stmt->fetch();

    if ($existingUser) {

        redirect(
            '/kost-management/public/register.php?error=' .
            urlencode('Email sudah terdaftar. Silakan gunakan email lain.')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Hash password
    |--------------------------------------------------------------------------
    */

    $hashedPassword = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    if ($hashedPassword === false) {
        throw new Exception(
            'Password gagal diproses.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Simpan user
    |--------------------------------------------------------------------------
    |
    | User yang mendaftar otomatis:
    |
    | role   = tenant
    | status = active
    |
    */

    $stmt = $pdo->prepare(
        "INSERT INTO users (
            name,
            email,
            phone,
            password,
            role,
            status
        ) VALUES (
            ?, ?, ?, ?, 'tenant', 'active'
        )"
    );

    $stmt->execute([
        $name,
        $email,
        $phone !== '' ? $phone : null,
        $hashedPassword
    ]);


    /*
    |--------------------------------------------------------------------------
    | Ambil ID user yang baru dibuat
    |--------------------------------------------------------------------------
    */

    $userId = $pdo->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | Ambil data user untuk session
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "SELECT
            id,
            name,
            email,
            phone,
            role,
            status
         FROM users
         WHERE id = ?
         LIMIT 1"
    );

    $stmt->execute([
        $userId
    ]);

    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception(
            'Akun berhasil dibuat tetapi data user tidak dapat dibaca.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Login otomatis setelah registrasi
    |--------------------------------------------------------------------------
    */

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    session_regenerate_id(true);

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
    | Activity log
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "INSERT INTO activity_logs (
            user_id,
            action,
            table_name,
            record_id,
            description
        ) VALUES (
            ?, 'register', 'users', ?, ?
        )"
    );

    $stmt->execute([
        $user['id'],
        $user['id'],
        'Membuat akun baru sebagai tenant.'
    ]);


    /*
    |--------------------------------------------------------------------------
    | Redirect berdasarkan role
    |--------------------------------------------------------------------------
    */

    redirectByRole();


} catch (PDOException $e) {

    /*
    |--------------------------------------------------------------------------
    | Error database
    |--------------------------------------------------------------------------
    */

    $_SESSION['register_error'] =
        'Registrasi gagal. Terjadi masalah pada database.';

    redirect(
        '/kost-management/public/register.php'
    );


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | Error umum
    |--------------------------------------------------------------------------
    */

    $_SESSION['register_error'] =
        $e->getMessage();

    redirect(
        '/kost-management/public/register.php'
    );
}
