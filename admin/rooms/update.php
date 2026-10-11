
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
$type = strtolower(trim($_POST['type'] ?? ''));
$capacity = filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT) ?: 1;
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


if ($type === '' || !in_array($type, ['standard', 'deluxe'], true)) {
    $_SESSION['room_error'] =
        'Tipe kamar wajib dipilih (standard atau deluxe).';

    redirect(
        '/kost-management/admin/rooms/edit.php?id=' .
        $roomId
    );
}


if ($capacity < 1 || $capacity > 20) {
    $_SESSION['room_error'] =
        'Jumlah orang (kapasitas) minimal 1 dan maksimal 20.';

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


$newUploadedPhoto = null;
$photoDestination = null;

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['photo'];
    $maxFileSize = 2 * 1024 * 1024; // 2MB

    if ($file['size'] > $maxFileSize) {
        $_SESSION['room_error'] = 'Ukuran foto kamar maksimal 2 MB.';
        redirect('/kost-management/admin/rooms/edit.php?id=' . $roomId);
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        $_SESSION['room_error'] = 'Format foto tidak valid. Gunakan JPG, JPEG, PNG, atau WEBP.';
        redirect('/kost-management/admin/rooms/edit.php?id=' . $roomId);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($mimeType, $allowedMimes, true)) {
        $_SESSION['room_error'] = 'File yang diupload bukan gambar yang valid.';
        redirect('/kost-management/admin/rooms/edit.php?id=' . $roomId);
    }

    $uploadDir = __DIR__ . '/../../uploads/rooms/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $newUploadedPhoto = 'room_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $photoDestination = $uploadDir . $newUploadedPhoto;

    if (!move_uploaded_file($file['tmp_name'], $photoDestination)) {
        $_SESSION['room_error'] = 'Gagal menyimpan foto kamar.';
        redirect('/kost-management/admin/rooms/edit.php?id=' . $roomId);
    }
}


try {

    /*
     * Pastikan kamar ada.
     */

    $stmt = $pdo->prepare(
        "SELECT
            id,
            room_number,
            photo,
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
     * Tentukan photo yang akan disimpan di DB.
     */
    $finalPhoto = $room['photo'];
    $oldPhotoToDelete = null;

    if ($newUploadedPhoto) {
        $finalPhoto = $newUploadedPhoto;
        if (!empty($room['photo'])) {
            $oldPhotoToDelete = __DIR__ . '/../../uploads/rooms/' . $room['photo'];
        }
    }


    /*
     * Update data kamar.
     */

    $stmt = $pdo->prepare(
        "UPDATE rooms
         SET
            room_number = ?,
            type = ?,
            capacity = ?,
            monthly_price = ?,
            facilities = ?,
            photo = ?
         WHERE id = ?"
    );

    $stmt->execute([
        $roomNumber,
        $type,
        $capacity,
        $monthlyPrice,
        $facilities !== '' ? $facilities : null,
        $finalPhoto,
        $roomId
    ]);


    /*
     * Activity log.
     */

    $description =
        'Mengubah data kamar ' .
        $roomNumber .
        '. Tipe: ' .
        roomTypeLabel($type) .
        ', kapasitas: ' .
        $capacity .
        ' orang, harga: ' .
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

    // Hapus file lama jika update sukses dan ada foto baru
    if ($oldPhotoToDelete && file_exists($oldPhotoToDelete)) {
        unlink($oldPhotoToDelete);
    }


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

    if ($photoDestination && file_exists($photoDestination)) {
        unlink($photoDestination);
    }

    $_SESSION['room_error'] =
        $e->getMessage();

    redirect(
        '/kost-management/admin/rooms/edit.php?id=' .
        $roomId
    );
}


