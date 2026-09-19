
<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/kost-management/admin/rooms/index.php');
}

$admin = currentUser();

$roomId = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);

if (!$roomId) {
    $_SESSION['room_error'] =
        'ID kamar tidak valid.';

    redirect(
        '/kost-management/admin/rooms/index.php'
    );
}


try {

    /*
     * Ambil data kamar.
     */

    $stmt = $pdo->prepare(
        "SELECT
            id,
            room_number,
            status
         FROM rooms
         WHERE id = ?
         LIMIT 1"
    );

    $stmt->execute([
        $roomId
    ]);

    $room = $stmt->fetch();


    if (!$room) {
        throw new Exception(
            'Kamar tidak ditemukan.'
        );
    }


    /*
     * Kamar yang tidak available
     * tidak boleh dihapus.
     */

    if ($room['status'] !== 'available') {
        throw new Exception(
            'Kamar hanya dapat dihapus jika statusnya tersedia.'
        );
    }


    /*
     * Cek apakah kamar pernah memiliki reservation.
     *
     * Jika pernah, jangan hapus agar histori
     * transaksi tetap terjaga.
     */

    $stmt = $pdo->prepare(
        "SELECT id
         FROM reservations
         WHERE room_id = ?
         LIMIT 1"
    );

    $stmt->execute([
        $roomId
    ]);

    if ($stmt->fetch()) {
        throw new Exception(
            'Kamar tidak dapat dihapus karena sudah memiliki histori reservasi. Gunakan kamar tersebut untuk data historis.'
        );
    }


    $pdo->beginTransaction();


    /*
     * Hapus kamar.
     */

    $stmt = $pdo->prepare(
        "DELETE FROM rooms
         WHERE id = ?"
    );

    $stmt->execute([
        $roomId
    ]);


    /*
     * Activity log.
     *
     * record_id tetap menggunakan ID kamar
     * sebelum dihapus.
     */

    $description =
        'Menghapus kamar ' .
        $room['room_number'] .
        '.';


    $stmt = $pdo->prepare(
        "INSERT INTO activity_logs (
            user_id,
            action,
            table_name,
            record_id,
            description
        ) VALUES (
            ?, 'delete', 'rooms', ?, ?
        )"
    );

    $stmt->execute([
        $admin['id'],
        $roomId,
        $description
    ]);


    $pdo->commit();


    $_SESSION['room_success'] =
        'Kamar ' .
        $room['room_number'] .
        ' berhasil dihapus.';


} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['room_error'] =
        $e->getMessage();
}


redirect(
    '/kost-management/admin/rooms/index.php'
);
