<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireTenant();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/kost-management/tenant/profile.php');
}

$user = refreshCurrentUser($pdo);

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');

if ($name === '') {
    $_SESSION['profile_error'] = 'Nama lengkap wajib diisi.';
    redirect('/kost-management/tenant/profile.php');
}

if ($phone === '') {
    $_SESSION['profile_error'] = 'Nomor WhatsApp / HP wajib diisi.';
    redirect('/kost-management/tenant/profile.php');
}

if ($address === '') {
    $_SESSION['profile_error'] = 'Alamat asal / KTP wajib diisi.';
    redirect('/kost-management/tenant/profile.php');
}

$uploadedKtp = null;
$ktpDestination = null;
$oldKtpToDelete = null;

// Handle KTP file upload
if (isset($_FILES['ktp_file']) && $_FILES['ktp_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['ktp_file'];
    $maxFileSize = 2 * 1024 * 1024; // 2MB

    if ($file['size'] > $maxFileSize) {
        $_SESSION['profile_error'] = 'Ukuran foto KTP maksimal 2 MB.';
        redirect('/kost-management/tenant/profile.php');
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        $_SESSION['profile_error'] = 'Format foto KTP tidak valid. Gunakan JPG, JPEG, PNG, atau WEBP.';
        redirect('/kost-management/tenant/profile.php');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($mimeType, $allowedMimes, true)) {
        $_SESSION['profile_error'] = 'File yang diupload bukan gambar KTP yang valid.';
        redirect('/kost-management/tenant/profile.php');
    }

    $uploadDir = __DIR__ . '/../uploads/ktp/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $uploadedKtp = 'ktp_' . $user['id'] . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $ktpDestination = $uploadDir . $uploadedKtp;

    if (!move_uploaded_file($file['tmp_name'], $ktpDestination)) {
        $_SESSION['profile_error'] = 'Gagal menyimpan foto KTP.';
        redirect('/kost-management/tenant/profile.php');
    }

    if (!empty($user['ktp_file'])) {
        $oldKtpToDelete = $uploadDir . $user['ktp_file'];
    }
}

try {
    $pdo->beginTransaction();

    $finalKtp = $uploadedKtp ?: $user['ktp_file'];

    if (empty($finalKtp)) {
        throw new Exception('Foto KTP wajib diunggah untuk verifikasi akun.');
    }

    // Tentukan verification_status:
    // Jika upload KTP baru atau sebelumnya belum verifikasi, status menjadi 'menunggu_verifikasi'
    $newVerificationStatus = $user['verification_status'];
    if ($uploadedKtp || $user['verification_status'] === 'belum_verifikasi') {
        $newVerificationStatus = 'menunggu_verifikasi';
    }

    $stmt = $pdo->prepare(
        "UPDATE users
         SET name = ?,
             phone = ?,
             address = ?,
             ktp_file = ?,
             verification_status = ?
         WHERE id = ?"
    );

    $stmt->execute([
        $name,
        $phone,
        $address,
        $finalKtp,
        $newVerificationStatus,
        $user['id']
    ]);

    // Tambah notifikasi
    $stmt = $pdo->prepare(
        "INSERT INTO notifications (user_id, title, message, type)
         VALUES (?, ?, ?, 'system')"
    );
    $stmt->execute([
        $user['id'],
        'Profil Diperbarui',
        'Data profil dan foto KTP Anda berhasil disimpan dan diajukan untuk verifikasi admin.'
    ]);

    // Activity log
    $stmt = $pdo->prepare(
        "INSERT INTO activity_logs (user_id, action, table_name, record_id, description)
         VALUES (?, 'update_profile', 'users', ?, ?)"
    );
    $stmt->execute([
        $user['id'],
        $user['id'],
        'Memperbarui data profil dan identitas KTP (Status: ' . $newVerificationStatus . ')'
    ]);

    $pdo->commit();

    if ($oldKtpToDelete && file_exists($oldKtpToDelete)) {
        unlink($oldKtpToDelete);
    }

    refreshCurrentUser($pdo);

    $_SESSION['profile_success'] = 'Profil dan foto KTP Anda berhasil disimpan. Status profil: ' . verificationStatusLabel($newVerificationStatus) . '.';
    redirect('/kost-management/tenant/profile.php');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($ktpDestination && file_exists($ktpDestination)) {
        unlink($ktpDestination);
    }

    $_SESSION['profile_error'] = $e->getMessage();
    redirect('/kost-management/tenant/profile.php');
}
