
<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/kost-management/admin/rooms/index.php');
}

$admin = currentUser();

$roomNumber = trim($_POST['room_number'] ?? '');
$type = trim($_POST['type'] ?? '');
$monthlyPrice = trim($_POST['monthly_price'] ?? '');
$facilities = trim($_POST['facilities'] ?? '');


if ($roomNumber === '') {
    $_SESSION['room_error'] = 'Nomor kamar wajib diisi.';
    redirect('/kost-management/admin/rooms/create.php');
}

if (mb_strlen($roomNumber) > 20) {
    $_SESSION['room_error'] = 'Nomor kamar maksimal 20 karakter.';
    redirect('/kost-management/admin/rooms/create.php');
}


if ($type === '') {
    $_SESSION['room_error'] = 'Tipe kamar wajib diisi.';
    redirect('/kost-management/admin/rooms/create.php');
}

if (mb_strlen($type) > 50) {
    $_SESSION['room_error'] = 'Tipe kamar maksimal 50 karakter.';
    redirect('/kost-management/admin/rooms/create.php');
}


if ($monthlyPrice === '' || !is_numeric($monthlyPrice)) {
    $_SESSION['room_error'] = 'Harga kamar tidak valid.';
    redirect('/kost-management/admin/rooms/create.php');
}

$monthlyPrice = (float) $monthlyPrice;

if ($monthlyPrice <= 0) {
    $_SESSION['room_error'] = 'Harga kamar harus lebih dari 0.';
    redirect('/kost-management/admin/rooms/create.php');
}


try {

    /*
     * Cek nomor kamar.
     */

    $stmt = $pdo->prepare(
        "SELECT id
         FROM rooms
         WHERE room_number = ?
         LIMIT 1"
    );

    $stmt->execute([
        $roomNumber
    ]);

    if ($stmt->fetch()) {
        throw new Exception(
            'Nomor kamar tersebut sudah digunakan.'
        );
    }


    $pdo->beginTransaction();


    /*
     * Insert kamar.
     */

    $stmt = $pdo->prepare(
        "INSERT INTO rooms (
            room_number,
            type,
            monthly_price,
            facilities,
            status
        ) VALUES (
            ?, ?, ?, ?, 'available'
        )"
    );

    $stmt->execute([
        $roomNumber,
        $type,
        $monthlyPrice,
        $facilities !== '' ? $facilities : null
    ]);


    $roomId = $pdo->lastInsertId();


    /*
     * Activity log.
     */

    $description =
        'Menambahkan kamar ' .
        $roomNumber .
        ' dengan tipe ' .
        $type .
        ' dan harga ' .
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
            ?, 'create', 'rooms', ?, ?
        )"
    );

    $stmt->execute([
        $admin['id'],
        $roomId,
        $description
    ]);


    $pdo->commit();


    $_SESSION['room_success'] =
        'Kamar ' . $roomNumber . ' berhasil ditambahkan.';

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
        '/kost-management/admin/rooms/create.php'
    );
}

