
<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/kost-management/admin/payments/index.php');
}

$admin = currentUser();

$paymentId = filter_input(
    INPUT_POST,
    'payment_id',
    FILTER_VALIDATE_INT
);

$rejectionReason = trim(
    $_POST['rejection_reason'] ?? ''
);

if (!$paymentId) {
    $_SESSION['payment_error'] =
        'ID pembayaran tidak valid.';

    redirect(
        '/kost-management/admin/payments/index.php'
    );
}

if ($rejectionReason === '') {
    $_SESSION['payment_error'] =
        'Alasan penolakan wajib diisi.';

    redirect(
        '/kost-management/admin/payments/detail.php?id=' .
        (int) $paymentId
    );
}

if (mb_strlen($rejectionReason) > 1000) {
    $_SESSION['payment_error'] =
        'Alasan penolakan maksimal 1000 karakter.';

    redirect(
        '/kost-management/admin/payments/detail.php?id=' .
        (int) $paymentId
    );
}


try {

    $pdo->beginTransaction();


    /*
     * Ambil pembayaran dan kunci row.
     */

    $stmt = $pdo->prepare(
        "SELECT
            p.id,
            p.payment_number,
            p.status,

            b.id AS bill_id,
            b.bill_number,

            r.id AS reservation_id,
            r.user_id,

            r.room_id,
            rm.room_number

         FROM payments p

         INNER JOIN bills b
            ON b.id = p.bill_id

         INNER JOIN reservations r
            ON r.id = b.reservation_id

         INNER JOIN rooms rm
            ON rm.id = r.room_id

         WHERE p.id = ?

         FOR UPDATE"
    );

    $stmt->execute([
        $paymentId
    ]);

    $payment = $stmt->fetch();


    if (!$payment) {
        throw new Exception(
            'Pembayaran tidak ditemukan.'
        );
    }


    /*
     * Hanya pembayaran waiting_verification
     * yang dapat ditolak.
     */

    if ($payment['status'] !== 'waiting_verification') {
        throw new Exception(
            'Pembayaran ini sudah diproses sebelumnya.'
        );
    }


    /*
     * Payment menjadi rejected.
     */

    $stmt = $pdo->prepare(
        "UPDATE payments
         SET
            status = 'rejected',
            rejection_reason = ?
         WHERE id = ?"
    );

    $stmt->execute([
        $rejectionReason,
        $paymentId
    ]);


    /*
     * Bill kembali menjadi unpaid
     * agar tenant dapat upload ulang.
     */

    $stmt = $pdo->prepare(
        "UPDATE bills
         SET status = 'unpaid'
         WHERE id = ?"
    );

    $stmt->execute([
        $payment['bill_id']
    ]);


    /*
     * Reservation kembali ke waiting_payment.
     */

    $stmt = $pdo->prepare(
        "UPDATE reservations
         SET status = 'waiting_payment'
         WHERE id = ?"
    );

    $stmt->execute([
        $payment['reservation_id']
    ]);


    /*
     * Notifikasi tenant.
     */

    $notificationTitle =
        'Pembayaran Ditolak';

    $notificationMessage =
        'Bukti pembayaran untuk kamar ' .
        $payment['room_number'] .
        ' ditolak oleh admin. Alasan: ' .
        $rejectionReason .
        '. Silakan upload bukti pembayaran kembali.';


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
        $payment['user_id'],
        $notificationTitle,
        $notificationMessage
    ]);


    /*
     * Activity log.
     */

    $description =
        'Menolak pembayaran ' .
        $payment['payment_number'] .
        ' untuk tagihan ' .
        $payment['bill_number'] .
        '. Alasan: ' .
        $rejectionReason;


    $stmt = $pdo->prepare(
        "INSERT INTO activity_logs (
            user_id,
            action,
            table_name,
            record_id,
            description
        ) VALUES (
            ?, 'reject', 'payments', ?, ?
        )"
    );

    $stmt->execute([
        $admin['id'],
        $paymentId,
        $description
    ]);


    $pdo->commit();


    $_SESSION['payment_success'] =
        'Pembayaran berhasil ditolak. Tenant dapat mengupload bukti pembayaran kembali.';

    redirect(
        '/kost-management/admin/payments/index.php'
    );


} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['payment_error'] =
        $e->getMessage();

    redirect(
        '/kost-management/admin/payments/detail.php?id=' .
        (int) $paymentId
    );
}
