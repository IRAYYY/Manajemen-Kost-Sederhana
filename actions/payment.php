<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireTenant();

$user = currentUser();

/*
|--------------------------------------------------------------------------
| Pastikan request POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/kost-management/tenant/dashboard.php');
}

/*
|--------------------------------------------------------------------------
| Ambil bill_id
|--------------------------------------------------------------------------
*/

$billId = filter_input(
    INPUT_POST,
    'bill_id',
    FILTER_VALIDATE_INT
);

$amount = trim($_POST['amount'] ?? '');

if (!$billId || $amount === '') {

    $_SESSION['payment_error'] =
        'Data pembayaran belum lengkap.';

    redirect(
        '/kost-management/tenant/dashboard.php'
    );
}

/*
|--------------------------------------------------------------------------
| Validasi jumlah
|--------------------------------------------------------------------------
*/

if (!is_numeric($amount) || (float) $amount <= 0) {

    $_SESSION['payment_error'] =
        'Jumlah pembayaran tidak valid.';

    redirect(
        '/kost-management/tenant/dashboard.php'
    );
}

$amount = (float) $amount;

/*
|--------------------------------------------------------------------------
| Pastikan file ada
|--------------------------------------------------------------------------
*/

if (
    !isset($_FILES['proof_file']) ||
    $_FILES['proof_file']['error'] !== UPLOAD_ERR_OK
) {

    $_SESSION['payment_error'] =
        'Bukti pembayaran wajib diupload.';

    redirect(
        '/kost-management/public/payment.php?reservation_id=' .
        (int) ($_POST['reservation_id'] ?? 0)
    );
}

$file = $_FILES['proof_file'];

/*
|--------------------------------------------------------------------------
| Validasi ukuran
|--------------------------------------------------------------------------
|
| Maksimal 2 MB
|--------------------------------------------------------------------------
*/

$maxFileSize = 2 * 1024 * 1024;

if ($file['size'] > $maxFileSize) {

    $_SESSION['payment_error'] =
        'Ukuran file maksimal 2 MB.';

    redirect(
        '/kost-management/public/payment.php?reservation_id=' .
        (int) ($_POST['reservation_id'] ?? 0)
    );
}

/*
|--------------------------------------------------------------------------
| Validasi extension
|--------------------------------------------------------------------------
*/

$allowedExtensions = [
    'jpg',
    'jpeg',
    'png',
    'webp'
];

$extension = strtolower(
    pathinfo(
        $file['name'],
        PATHINFO_EXTENSION
    )
);

if (!in_array($extension, $allowedExtensions, true)) {

    $_SESSION['payment_error'] =
        'Format file tidak diperbolehkan. Gunakan JPG, JPEG, PNG, atau WEBP.';

    redirect(
        '/kost-management/public/payment.php?reservation_id=' .
        (int) ($_POST['reservation_id'] ?? 0)
    );
}

/*
|--------------------------------------------------------------------------
| Validasi MIME type menggunakan finfo
|--------------------------------------------------------------------------
*/

$finfo = new finfo(FILEINFO_MIME_TYPE);

$mimeType = $finfo->file(
    $file['tmp_name']
);

$allowedMimeTypes = [
    'image/jpeg',
    'image/png',
    'image/webp'
];

if (!in_array($mimeType, $allowedMimeTypes, true)) {

    $_SESSION['payment_error'] =
        'File yang diupload bukan gambar yang valid.';

    redirect(
        '/kost-management/public/payment.php?reservation_id=' .
        (int) ($_POST['reservation_id'] ?? 0)
    );
}

try {

    /*
    |--------------------------------------------------------------------------
    | Ambil data tagihan + reservasi
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "SELECT
            b.id AS bill_id,
            b.bill_number,
            b.amount AS bill_amount,
            b.status AS bill_status,

            r.id AS reservation_id,
            r.room_id,
            r.status AS reservation_status,

            rm.room_number

         FROM bills b

         INNER JOIN reservations r
            ON r.id = b.reservation_id

         INNER JOIN rooms rm
            ON rm.id = r.room_id

         WHERE b.id = ?
           AND r.user_id = ?

         LIMIT 1"
    );

    $stmt->execute([
        $billId,
        $user['id']
    ]);

    $bill = $stmt->fetch();

    if (!$bill) {
        throw new Exception(
            'Tagihan tidak ditemukan.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Validasi status tagihan
    |--------------------------------------------------------------------------
    */

    if (
        !in_array(
            $bill['bill_status'],
            ['unpaid'],
            true
        )
    ) {

        /*
        | Jika pembayaran sebelumnya ditolak,
        | user boleh upload kembali.
        */

        $stmt = $pdo->prepare(
            "SELECT status
             FROM payments
             WHERE bill_id = ?
             ORDER BY created_at DESC
             LIMIT 1"
        );

        $stmt->execute([$billId]);

        $latestPayment = $stmt->fetch();

        if (
            !$latestPayment ||
            $latestPayment['status'] !== 'rejected'
        ) {
            throw new Exception(
                'Tagihan ini tidak dapat dibayar kembali saat ini.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Validasi jumlah pembayaran
    |--------------------------------------------------------------------------
    */

    $billAmount = (float) $bill['bill_amount'];

    if (abs($amount - $billAmount) > 0.01) {

        throw new Exception(
            'Jumlah pembayaran harus sesuai dengan total tagihan: ' .
            rupiah($billAmount)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Mulai transaksi
    |--------------------------------------------------------------------------
    */

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Buat folder upload jika belum ada
    |--------------------------------------------------------------------------
    */

    $uploadDirectory =
        __DIR__ . '/../uploads/payment-proofs/';

    if (!is_dir($uploadDirectory)) {

        if (!mkdir(
            $uploadDirectory,
            0755,
            true
        )) {
            throw new Exception(
                'Folder upload tidak dapat dibuat.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Buat nama file unik
    |--------------------------------------------------------------------------
    */

    $fileName =
        'payment_' .
        $billId .
        '_' .
        bin2hex(random_bytes(8)) .
        '.' .
        $extension;

    $destination =
        $uploadDirectory . $fileName;

    /*
    |--------------------------------------------------------------------------
    | Pindahkan file
    |--------------------------------------------------------------------------
    */

    if (!move_uploaded_file(
        $file['tmp_name'],
        $destination
    )) {

        throw new Exception(
            'Bukti pembayaran gagal disimpan.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Nomor pembayaran
    |--------------------------------------------------------------------------
    */

    $paymentNumber =
        'PAY-' .
        date('YmdHis') .
        '-' .
        random_int(100, 999);

    /*
    |--------------------------------------------------------------------------
    | Jika ada pembayaran rejected sebelumnya,
    | gunakan record baru.
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "INSERT INTO payments (
            bill_id,
            payment_number,
            amount,
            payment_date,
            proof_file,
            status
        ) VALUES (
            ?, ?, ?, NOW(), ?, 'waiting_verification'
        )"
    );

    $stmt->execute([
        $billId,
        $paymentNumber,
        $amount,
        $fileName
    ]);

    $paymentId = $pdo->lastInsertId();

    /*
    |--------------------------------------------------------------------------
    | Update status tagihan
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "UPDATE bills
         SET status = 'waiting_verification'
         WHERE id = ?"
    );

    $stmt->execute([
        $billId
    ]);

    /*
    |--------------------------------------------------------------------------
    | Update status reservasi
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "UPDATE reservations
         SET status = 'waiting_verification'
         WHERE id = ?"
    );

    $stmt->execute([
        $bill['reservation_id']
    ]);

    /*
    |--------------------------------------------------------------------------
    | Notifikasi tenant
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "INSERT INTO notifications (
            user_id,
            title,
            message,
            type
        ) VALUES (
            ?, ?, ?, 'payment'
        )"
    );

    $stmt->execute([
        $user['id'],
        'Pembayaran Dikirim',
        'Bukti pembayaran untuk kamar ' .
        $bill['room_number'] .
        ' berhasil dikirim dan sedang menunggu verifikasi admin.'
    ]);

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
            ?, ?, ?, ?, ?
        )"
    );

    $stmt->execute([
        $user['id'],
        'create',
        'payments',
        $paymentId,
        'Mengirim bukti pembayaran ' .
        $paymentNumber .
        ' untuk tagihan ' .
        $bill['bill_number']
    ]);

    /*
    |--------------------------------------------------------------------------
    | Commit
    |--------------------------------------------------------------------------
    */

    $pdo->commit();

    /*
    |--------------------------------------------------------------------------
    | Sukses
    |--------------------------------------------------------------------------
    */

    $_SESSION['payment_success'] =
        'Bukti pembayaran berhasil dikirim dan sedang menunggu verifikasi admin.';

    redirect(
        '/kost-management/public/payment.php?reservation_id=' .
        $bill['reservation_id']
    );

} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | Rollback
    |--------------------------------------------------------------------------
    */

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    /*
    |--------------------------------------------------------------------------
    | Hapus file jika database gagal
    |--------------------------------------------------------------------------
    */

    if (
        isset($destination) &&
        file_exists($destination)
    ) {
        unlink($destination);
    }

    /*
    |--------------------------------------------------------------------------
    | Error
    |--------------------------------------------------------------------------
    */

    $_SESSION['payment_error'] =
        $e->getMessage();

    /*
    |--------------------------------------------------------------------------
    | Kembali ke halaman pembayaran
    |--------------------------------------------------------------------------
    */

    $reservationId = (int) (
        $_POST['reservation_id'] ?? 0
    );

    if ($reservationId) {

        redirect(
            '/kost-management/public/payment.php?reservation_id=' .
            $reservationId
        );
    }

    redirect(
        '/kost-management/tenant/dashboard.php'
    );
}

