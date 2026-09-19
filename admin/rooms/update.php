
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

$roomNumber = trim($_POST['room_number'] ?? '');
$type = trim($_POST['type'] ?? '');
$monthlyPrice = trim($_POST['monthly_price'] ?? '');
$facilities = trim($_POST['facilities'] ?? '');


if (!$roomId) {
    $_SESSION['room_error'] =
        'ID kamar tidak valid.';

    redirect(
        '/kost-management/admin/rooms/index.php'
    );
}


if ($roomNumber === '') {
    $_SESSION['room_error'] =
        'Nomor kamar wajib diisi.';

    redirect(
        '/kost-management/admin/rooms/edit.php?id=' .
        $roomId
    );
}


if (mb_strlen($roomNumber) > 20) {
    $_SESSION['room_error'] =
        'Nomor kamar maksimal 20 karakter.';

    redirect(
        '/kost-management/admin/rooms/edit.php?id=' .
        $roomId
    );
}


if ($type === '') {
    $_SESSION['room_error'] =
        'Tipe kamar wajib diisi.';

    redirect(
        '/kost-management/admin/rooms/edit.php?id=' .
        $roomId
    );
}


if (mb_strlen($type) > 50) {
    $_SESSION['room_error'] =
        'Tipe kamar maksimal 50 karakter.';

    redirect(
        '/kost-management/admin/rooms/edit.php?id=' .
        $roomId
    );
}


if ($monthlyPrice === '' || !is_numeric($monthlyPrice)) {
    $_SESSION['room_error'] =
        'Harga kamar tidak valid.';

    redirect(
        '/kost-management/admin/rooms/edit.php?id=' .
        $roomId
    );
}

$monthlyPrice = (float) $monthlyPrice;


if ($monthlyPrice <= 0) {
    $_SESSION['room_error'] =
        'Harga kamar harus lebih dari 0.';

    redirect(
        '/kost-management/admin/rooms/edit.php?id=' .
        $roomId
    );
}


try {

    /*
     * Pastikan kamar ada.
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
     * Cek nomor kamar tidak dipakai
     * kamar lain.
     */

    $stmt = $pdo->prepare(
        "SELECT id
         FROM rooms
         WHERE room_number = ?
         AND id != ?
         LIMIT 1"
    );

    $stmt->execute([
        $roomNumber,
        $roomId
    ]);

    if ($stmt->fetch()) {
        throw new Exception(
            'Nomor kamar tersebut sudah digunakan oleh kamar lain.'
        );
    }


    $pdo->beginTransaction();


    /*
     * Update data kamar.
     */

    $stmt = $pdo->prepare(
        "UPDATE rooms
         SET
            room_number = ?,
            type = ?,
            monthly_price = ?,
            facilities = ?
         WHERE id = ?"
    );

    $stmt->execute([
        $roomNumber,
        $type,
        $monthlyPrice,
        $facilities !== '' ? $facilities : null,
        $roomId
    ]);


    /*
     * Activity log.
     */

    $description =
        'Mengubah data kamar ' .
        $roomNumber .
        '. Tipe: ' .
        $type .
        ', harga: ' .
        rupiah($monthlyPrice) .
        ' per bulan.';


    $stmt = $pdo->prepare(
        "INSERT INTO activity_logs (
            user_id,
            action,
            table_name,
            record_id,
            description
        ) VALUES (
            ?, 'update', 'rooms', ?, ?
        )"
    );

    $stmt->execute([
        $admin['id'],
        $roomId,
        $description
    ]);


    $pdo->commit();


    $_SESSION['room_success'] =
        'Data kamar ' .
        $roomNumber .
        ' berhasil diperbarui.';


    redirect(
        '/kost-management/admin/rooms/index.php'
    );


} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['room_error'] =
        $e->getMessage();

    redirect(
        '/kost-management/admin/rooms/edit.php?id=' .
        $roomId
    );
}

