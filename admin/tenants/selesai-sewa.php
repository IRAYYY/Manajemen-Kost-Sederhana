
<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/kost-management/admin/tenants/index.php');
}

$admin = currentUser();

$reservationId = filter_input(
    INPUT_POST,
    'reservation_id',
    FILTER_VALIDATE_INT
);

if (!$reservationId) {

    $_SESSION['tenant_error'] =
        'ID reservation tidak valid.';

    redirect(
        '/kost-management/admin/tenants/index.php'
    );
}


try {

    $pdo->beginTransaction();


    /*
     * Ambil reservation dan lock row.
     */

    $stmt = $pdo->prepare(
        "SELECT
            r.id,
            r.user_id,
            r.room_id,
            r.status,
            r.start_date,
            r.end_date,

            u.name AS tenant_name,

            rm.room_number,
            rm.status AS room_status

         FROM reservations r

         INNER JOIN users u
            ON u.id = r.user_id

         INNER JOIN rooms rm
            ON rm.id = r.room_id

         WHERE r.id = ?

         FOR UPDATE"
    );

    $stmt->execute([
        $reservationId
    ]);

    $reservation = $stmt->fetch();


    if (!$reservation) {
        throw new Exception(
            'Reservation tidak ditemukan.'
        );
    }


    /*
     * Hanya reservation aktif
     * yang dapat diakhiri.
     */

    if ($reservation['status'] !== 'active') {
        throw new Exception(
            'Reservation ini tidak sedang aktif.'
        );
    }


    /*
     * Reservation menjadi completed.
     */

    $stmt = $pdo->prepare(
        "UPDATE reservations
         SET status = 'completed'
         WHERE id = ?"
    );

    $stmt->execute([
        $reservationId
    ]);


    /*
     * Kamar kembali tersedia.
     */

    $stmt = $pdo->prepare(
        "UPDATE rooms
         SET status = 'available'
         WHERE id = ?"
    );

    $stmt->execute([
        $reservation['room_id']
    ]);


    /*
     * Notifikasi tenant.
     */

    $notificationTitle =
        'Masa Sewa Selesai';

    $notificationMessage =
        'Masa sewa Anda untuk kamar ' .
        $reservation['room_number'] .
        ' telah diakhiri oleh admin.';


    $stmt = $pdo->prepare(
        "INSERT INTO notifications (
            user_id,
            title,
            message,
            type
        ) VALUES (
            ?, ?, ?, 'reservation'
        )"
    );

    $stmt->execute([
        $reservation['user_id'],
        $notificationTitle,
        $notificationMessage
    ]);


    /*
     * Activity log.
     */

    $description =
        'Mengakhiri masa sewa tenant ' .
        $reservation['tenant_name'] .
        ' pada kamar ' .
        $reservation['room_number'] .
        '. Reservation #' .
        $reservationId .
        ' menjadi completed dan kamar menjadi available.';


    $stmt = $pdo->prepare(
        "INSERT INTO activity_logs (
            user_id,
            action,
            table_name,
            record_id,
            description
        ) VALUES (
            ?, 'complete_rental', 'reservations', ?, ?
        )"
    );

    $stmt->execute([
        $admin['id'],
        $reservationId,
        $description
    ]);


    $pdo->commit();


    $_SESSION['tenant_success'] =
        'Masa sewa ' .
        $reservation['tenant_name'] .
        ' berhasil diakhiri. Kamar ' .
        $reservation['room_number'] .
        ' sekarang tersedia.';


    redirect(
        '/kost-management/admin/tenants/index.php'
    );


} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['tenant_error'] =
        $e->getMessage();

    redirect(
        '/kost-management/admin/tenants/detail.php?id=' .
        (int) $reservationId
    );
}

