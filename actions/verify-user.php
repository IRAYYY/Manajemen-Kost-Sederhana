<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/kost-management/admin/users/index.php');
}

$admin = currentUser();

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$action = trim($_POST['action'] ?? '');
$notes  = trim($_POST['notes'] ?? '');
$tab    = trim($_POST['tab'] ?? 'menunggu_verifikasi');

if (!$userId || !in_array($action, ['verify', 'reject'], true)) {
    $_SESSION['user_verify_error'] = 'Permintaan tidak valid.';
    redirect('/kost-management/admin/users/index.php?tab=' . urlencode($tab));
}

// Load the user
$stmt = $pdo->prepare(
    "SELECT id, name, email, ktp_file, verification_status
     FROM users
     WHERE id = ? AND role = 'tenant'
     LIMIT 1"
);
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['user_verify_error'] = 'User tidak ditemukan.';
    redirect('/kost-management/admin/users/index.php?tab=' . urlencode($tab));
}

try {
    $pdo->beginTransaction();

    if ($action === 'verify') {
        if (empty($user['ktp_file'])) {
            throw new Exception('User belum mengunggah foto KTP. Tidak dapat diverifikasi.');
        }

        $newStatus = 'terverifikasi';
        $verifiedAt = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare(
            "UPDATE users
             SET verification_status = ?,
                 verification_notes  = ?,
                 verified_at         = ?
             WHERE id = ?"
        );
        $stmt->execute([$newStatus, $notes ?: null, $verifiedAt, $userId]);

        // Notifikasi ke user
        $stmt = $pdo->prepare(
            "INSERT INTO notifications (user_id, title, message, type)
             VALUES (?, 'Profil Terverifikasi', 'Selamat! Profil dan foto KTP Anda telah diverifikasi oleh admin. Anda kini dapat memesan kamar.', 'system')"
        );
        $stmt->execute([$userId]);

        // Activity log
        $stmt = $pdo->prepare(
            "INSERT INTO activity_logs (user_id, action, table_name, record_id, description)
             VALUES (?, 'verify_user', 'users', ?, ?)"
        );
        $stmt->execute([
            $admin['id'],
            $userId,
            'Memverifikasi akun tenant: ' . $user['name'] . ' (' . $user['email'] . ')'
        ]);

        $successMsg = 'Akun ' . $user['name'] . ' berhasil diverifikasi.';
        $redirectTab = 'terverifikasi';

    } else {
        // Reject / reset to belum_verifikasi
        $newStatus = 'belum_verifikasi';

        $stmt = $pdo->prepare(
            "UPDATE users
             SET verification_status = ?,
                 verification_notes  = ?,
                 verified_at         = NULL
             WHERE id = ?"
        );
        $stmt->execute([$newStatus, $notes ?: null, $userId]);

        // Notifikasi ke user
        $notifMsg = $notes
            ? 'Verifikasi profil Anda ditolak oleh admin. Alasan: ' . $notes . '. Silakan perbaiki data dan ajukan ulang.'
            : 'Verifikasi profil Anda ditolak oleh admin. Silakan perbaiki data dan foto KTP Anda, lalu ajukan ulang.';

        $stmt = $pdo->prepare(
            "INSERT INTO notifications (user_id, title, message, type)
             VALUES (?, 'Verifikasi Profil Ditolak', ?, 'system')"
        );
        $stmt->execute([$userId, $notifMsg]);

        // Activity log
        $stmt = $pdo->prepare(
            "INSERT INTO activity_logs (user_id, action, table_name, record_id, description)
             VALUES (?, 'reject_user', 'users', ?, ?)"
        );
        $stmt->execute([
            $admin['id'],
            $userId,
            'Menolak / mencabut verifikasi akun tenant: ' . $user['name'] . ' (' . $user['email'] . ')'
        ]);

        $successMsg = 'Verifikasi akun ' . $user['name'] . ' ditolak / direset.';
        $redirectTab = 'belum_verifikasi';
    }

    $pdo->commit();

    $_SESSION['user_verify_success'] = $successMsg;
    redirect('/kost-management/admin/users/index.php?tab=' . urlencode($redirectTab));

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['user_verify_error'] = $e->getMessage();
    redirect('/kost-management/admin/users/index.php?tab=' . urlencode($tab));
}
