
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireTenant();

$user = currentUser();


/*
|--------------------------------------------------------------------------
| Hanya menerima POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/kost-management/tenant/dashboard.php');
}


/*
|--------------------------------------------------------------------------
| Ambil data dari form
|--------------------------------------------------------------------------
*/

$billId = filter_input(
    INPUT_POST,
    'bill_id',
    FILTER_VALIDATE_INT
);

$reservationId = filter_input(
    INPUT_POST,
    'reservation_id',
    FILTER_VALIDATE_INT
);

$amount = trim(
    $_POST['amount'] ?? ''
);


/*
|--------------------------------------------------------------------------
| Validasi data dasar
|--------------------------------------------------------------------------
*/

if (!$billId || !$reservationId || $amount === '') {

    $_SESSION['payment_error'] =
        'Data pembayaran belum lengkap.';

    redirectPayment($reservationId);
}


/*
|--------------------------------------------------------------------------
| Validasi jumlah pembayaran
|--------------------------------------------------------------------------
*/

if (
    !is_numeric($amount) ||
    (float) $amount <= 0
) {

    $_SESSION['payment_error'] =
        'Jumlah pembayaran tidak valid.';

    redirectPayment($reservationId);
}

$amount = (float) $amount;


/*
|--------------------------------------------------------------------------
| Validasi file upload
|--------------------------------------------------------------------------
*/

if (
    !isset($_FILES['proof_file']) ||
    $_FILES['proof_file']['error'] !== UPLOAD_ERR_OK
) {

    $_SESSION['payment_error'] =
        'Bukti pembayaran wajib diupload.';

    redirectPayment($reservationId);
}


$file = $_FILES['proof_file'];


/*
|--------------------------------------------------------------------------
| Validasi ukuran file
|--------------------------------------------------------------------------
|
| Maksimal 2 MB
|--------------------------------------------------------------------------
*/

$maxFileSize = 2 * 1024 * 1024;

if ($file['size'] > $maxFileSize) {

    $_SESSION['payment_error'] =
        'Ukuran file maksimal 2 MB.';

    redirectPayment($reservationId);
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

if (
    !in_array(
        $extension,
        $allowedExtensions,
        true
    )
) {

    $_SESSION['payment_error'] =
        'Format file tidak diperbolehkan. Gunakan JPG, JPEG, PNG, atau WEBP.';

    redirectPayment($reservationId);
}


/*
|--------------------------------------------------------------------------
| Validasi MIME type
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

if (
    !in_array(
        $mimeType,
        $allowedMimeTypes,
        true
    )
) {

    $_SESSION['payment_error'] =
        'File yang diupload bukan gambar yang valid.';

    redirectPayment($reservationId);
}


try {

    /*
    |--------------------------------------------------------------------------
    | Ambil tagihan dan reservasi
    |--------------------------------------------------------------------------
    |
    | Sekaligus memastikan bill memang milik tenant yang sedang login.
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
           AND r.id = ?
           AND r.user_id = ?

         LIMIT 1"
    );

    $stmt->execute([
        $billId,
        $reservationId,
        $user['id']
    ]);

    $bill = $stmt->fetch();


    if (!$bill) {

        throw new Exception(
            'Tagihan tidak ditemukan atau bukan milik Anda.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Ambil pembayaran terakhir
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "SELECT
            id,
            payment_number,
            amount,
            status,
            proof_file,
            rejection_reason,
            created_at

         FROM payments

         WHERE bill_id = ?

         ORDER BY created_at DESC

         LIMIT 1"
    );

    $stmt->execute([
        $billId
    ]);

    $latestPayment = $stmt->fetch();


    /*
    |--------------------------------------------------------------------------
    | Tentukan apakah ini upload pertama atau upload ulang
    |--------------------------------------------------------------------------
    */

    $isFirstPayment =
        !$latestPayment;

    $isRetry =
        $latestPayment &&
        $latestPayment['status'] === 'rejected';


    /*
    |--------------------------------------------------------------------------
    | Validasi status pembayaran
    |--------------------------------------------------------------------------
    |
    | Yang diperbolehkan:
    |
    | 1. Belum pernah membayar
    | 2. Pembayaran terakhir ditolak
    |
    |--------------------------------------------------------------------------
    */

    if (
        !$isFirstPayment &&
        !$isRetry
    ) {

        if (
            $latestPayment['status'] ===
            'waiting_verification'
        ) {

            throw new Exception(
                'Bukti pembayaran Anda sedang menunggu verifikasi admin.'
            );
        }


        if (
            $latestPayment['status'] ===
            'verified'
        ) {

            throw new Exception(
                'Pembayaran untuk tagihan ini sudah diverifikasi.'
            );
        }


        throw new Exception(
            'Pembayaran tidak dapat dilakukan saat ini.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Validasi status tagihan
    |--------------------------------------------------------------------------
    |
    | Upload pertama:
    | unpaid
    |
    | Upload ulang:
    | unpaid
    |
    | Jika status masih waiting_verification,
    | berarti pembayaran sebelumnya belum selesai diproses.
    |--------------------------------------------------------------------------
    */

    if (
        $bill['bill_status'] !== 'unpaid'
    ) {

        throw new Exception(
            'Tagihan tidak berada dalam status yang dapat dibayar.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Validasi status reservasi
    |--------------------------------------------------------------------------
    */

    if (
        !in_array(
            $bill['reservation_status'],
            [
                'waiting_payment',
                'waiting_verification'
            ],
            true
        )
    ) {

        throw new Exception(
            'Reservasi tidak dapat menerima pembayaran saat ini.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Jumlah pembayaran harus sama dengan tagihan
    |--------------------------------------------------------------------------
    */

    $billAmount = (float) $bill['bill_amount'];

    if (
        abs(
            $amount - $billAmount
        ) > 0.01
    ) {

        throw new Exception(
            'Jumlah pembayaran harus sesuai dengan total tagihan: ' .
            rupiah($billAmount)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Mulai transaksi database
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

        if (
            !mkdir(
                $uploadDirectory,
                0755,
                true
            )
        ) {

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
        bin2hex(
            random_bytes(8)
        ) .
        '.' .
        $extension;


    $destination =
        $uploadDirectory .
        $fileName;


    /*
    |--------------------------------------------------------------------------
    | Pindahkan file
    |--------------------------------------------------------------------------
    */

    if (
        !move_uploaded_file(
            $file['tmp_name'],
            $destination
        )
    ) {

        throw new Exception(
            'Bukti pembayaran gagal disimpan.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Buat nomor pembayaran
    |--------------------------------------------------------------------------
    */

    $paymentNumber =
        'PAY-' .
        date('YmdHis') .
        '-' .
        random_int(
            100,
            999
        );


    /*
    |--------------------------------------------------------------------------
    | Simpan pembayaran
    |--------------------------------------------------------------------------
    |
    | Upload pertama:
    | payment baru → waiting_verification
    |
    | Upload ulang:
    | payment baru → waiting_verification
    |
    | Record rejected sebelumnya tetap disimpan sebagai histori.
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


    $paymentId =
        $pdo->lastInsertId();


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
        $reservationId
    ]);


    /*
    |--------------------------------------------------------------------------
    | Notifikasi tenant
    |--------------------------------------------------------------------------
    */

    $notificationTitle =
        $isRetry
            ? 'Bukti Pembayaran Dikirim Ulang'
            : 'Bukti Pembayaran Dikirim';


    $notificationMessage =
        $isRetry
            ? 'Bukti pembayaran untuk kamar ' .
              $bill['room_number'] .
              ' berhasil dikirim ulang dan sedang menunggu verifikasi admin.'

            : 'Bukti pembayaran untuk kamar ' .
              $bill['room_number'] .
              ' berhasil dikirim dan sedang menunggu verifikasi admin.';


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
        $notificationTitle,
        $notificationMessage
    ]);


    /*
    |--------------------------------------------------------------------------
    | Activity log
    |--------------------------------------------------------------------------
    */

    $action =
        $isRetry
            ? 'retry_payment'
            : 'create';


    $description =
        $isRetry
            ? 'Mengirim ulang bukti pembayaran ' .
              $paymentNumber .
              ' untuk tagihan ' .
              $bill['bill_number']

            : 'Mengirim bukti pembayaran ' .
              $paymentNumber .
              ' untuk tagihan ' .
              $bill['bill_number'];


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
        $action,
        'payments',
        $paymentId,
        $description
    ]);


    /*
    |--------------------------------------------------------------------------
    | Commit transaksi
    |--------------------------------------------------------------------------
    */

    $pdo->commit();


    /*
    |--------------------------------------------------------------------------
    | Pesan sukses
    |--------------------------------------------------------------------------
    */

    $_SESSION['payment_success'] =
        $isRetry
            ? 'Bukti pembayaran berhasil dikirim ulang dan sedang menunggu verifikasi admin.'
            : 'Bukti pembayaran berhasil dikirim dan sedang menunggu verifikasi admin.';


    /*
    |--------------------------------------------------------------------------
    | Kembali ke halaman pembayaran
    |--------------------------------------------------------------------------
    */

    redirectPayment(
        $reservationId
    );


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | Rollback
    |--------------------------------------------------------------------------
    */

    if (
        $pdo->inTransaction()
    ) {

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
    | Simpan pesan error
    |--------------------------------------------------------------------------
    */

    $_SESSION['payment_error'] =
        $e->getMessage();


    /*
    |--------------------------------------------------------------------------
    | Kembali ke halaman pembayaran
    |--------------------------------------------------------------------------
    */

    redirectPayment(
        $reservationId
    );
}


/*
|--------------------------------------------------------------------------
| Function redirect
|--------------------------------------------------------------------------
*/

function redirectPayment($reservationId)
{
    if (!$reservationId) {

        redirect(
            '/kost-management/tenant/dashboard.php'
        );
    }

    redirect(
        '/kost-management/public/payment.php?reservation_id=' .
        (int) $reservationId
    );
}
