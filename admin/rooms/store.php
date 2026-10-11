
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
$type = strtolower(trim($_POST['type'] ?? ''));
$capacity = filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT) ?: 1;
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


if ($type === '' || !in_array($type, ['standard', 'deluxe'], true)) {
    $_SESSION['room_error'] = 'Tipe kamar wajib dipilih (standard atau deluxe).';
    redirect('/kost-management/admin/rooms/create.php');
}


if ($capacity < 1 || $capacity > 20) {
    $_SESSION['room_error'] = 'Jumlah orang (kapasitas) minimal 1 dan maksimal 20.';
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


$uploadedPhoto = null;
$photoDestination = null;

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['photo'];
    $maxFileSize = 2 * 1024 * 1024; // 2MB

    if ($file['size'] > $maxFileSize) {
        $_SESSION['room_error'] = 'Ukuran foto kamar maksimal 2 MB.';
        redirect('/kost-management/admin/rooms/create.php');
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        $_SESSION['room_error'] = 'Format foto tidak valid. Gunakan JPG, JPEG, PNG, atau WEBP.';
        redirect('/kost-management/admin/rooms/create.php');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($mimeType, $allowedMimes, true)) {
        $_SESSION['room_error'] = 'File yang diupload bukan gambar yang valid.';
        redirect('/kost-management/admin/rooms/create.php');
    }

    $uploadDir = __DIR__ . '/../../uploads/rooms/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $uploadedPhoto = 'room_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $photoDestination = $uploadDir . $uploadedPhoto;

    if (!move_uploaded_file($file['tmp_name'], $photoDestination)) {
        $_SESSION['room_error'] = 'Gagal menyimpan foto kamar.';
        redirect('/kost-management/admin/rooms/create.php');
    }
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
            capacity,
            monthly_price,
            facilities,
            photo,
            status
        ) VALUES (
            ?, ?, ?, ?, ?, ?, 'available'
        )"
    );

    $stmt->execute([
        $roomNumber,
        $type,
        $capacity,
        $monthlyPrice,
        $facilities !== '' ? $facilities : null,
        $uploadedPhoto
    ]);


    $roomId = $pdo->lastInsertId();


    /*
     * Activity log.
     */

    $description =
        'Menambahkan kamar ' .
        $roomNumber .
        ' dengan tipe ' .
        roomTypeLabel($type) .
        ', kapasitas ' .
        $capacity .
        ' orang, dan harga ' .
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

    if ($photoDestination && file_exists($photoDestination)) {
        unlink($photoDestination);
    }

    $_SESSION['room_error'] =
        $e->getMessage();

    redirect(
        '/kost-management/admin/rooms/create.php'
    );
}


