
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireTenant();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/kost-management/public/rooms.php');
}

$user = currentUser();

$roomId = filter_input(
    INPUT_POST,
    'room_id',
    FILTER_VALIDATE_INT
);

$startPeriod = trim($_POST['start_period'] ?? '');
$endPeriod   = trim($_POST['end_period'] ?? '');

if (!$roomId || !$startPeriod || !$endPeriod) {
    $_SESSION['booking_error'] = 'Data booking belum lengkap.';
    redirect('/kost-management/public/rooms.php');
}

/*
|--------------------------------------------------------------------------
| Validasi format periode
|--------------------------------------------------------------------------
| Format yang diterima:
| YYYY-MM
| Contoh:
| 2026-10
| 2026-11
|--------------------------------------------------------------------------
*/

if (
    !preg_match('/^\d{4}-\d{2}$/', $startPeriod) ||
    !preg_match('/^\d{4}-\d{2}$/', $endPeriod)
) {
    $_SESSION['booking_error'] = 'Format periode booking tidak valid.';
    redirect(
        '/kost-management/public/booking.php?room_id=' . $roomId
    );
}

try {

    /*
    |--------------------------------------------------------------------------
    | Buat objek tanggal periode
    |--------------------------------------------------------------------------
    */

    $startDate = DateTime::createFromFormat(
        '!Y-m-d',
        $startPeriod . '-01'
    );

    $endDate = DateTime::createFromFormat(
        '!Y-m-d',
        $endPeriod . '-01'
    );

    if (!$startDate || !$endDate) {
        throw new Exception('Periode booking tidak valid.');
    }

    /*
    |--------------------------------------------------------------------------
    | Pastikan bulan benar-benar valid
    |--------------------------------------------------------------------------
    */

    if (
        $startDate->format('Y-m') !== $startPeriod ||
        $endDate->format('Y-m') !== $endPeriod
    ) {
        throw new Exception('Periode booking tidak valid.');
    }

    /*
    |--------------------------------------------------------------------------
    | Booking minimal dimulai bulan depan
    |--------------------------------------------------------------------------
    */

    $minimumStartDate = new DateTime(
        'first day of next month'
    );

    $minimumStartDate->setTime(0, 0, 0);

    if ($startDate < $minimumStartDate) {
        throw new Exception(
            'Periode sewa paling cepat dimulai bulan depan.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | End period tidak boleh sebelum start period
    |--------------------------------------------------------------------------
    */

    if ($endDate < $startDate) {
        throw new Exception(
            'Bulan selesai tidak boleh sebelum bulan mulai.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Hitung durasi dalam bulan
    |--------------------------------------------------------------------------
    */

    $durationMonths =
        (($endDate->format('Y') - $startDate->format('Y')) * 12)
        + ($endDate->format('n') - $startDate->format('n'))
        + 1;

    /*
    |--------------------------------------------------------------------------
    | Batasi maksimal 12 bulan
    |--------------------------------------------------------------------------
    */

    if ($durationMonths < 1 || $durationMonths > 12) {
        throw new Exception(
            'Durasi sewa harus antara 1 sampai 12 bulan.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Tentukan tanggal akhir sebenarnya
    |--------------------------------------------------------------------------
    */

    $endDate->modify('last day of this month');

    $startDateString = $startDate->format('Y-m-d');
    $endDateString   = $endDate->format('Y-m-d');

    /*
    |--------------------------------------------------------------------------
    | Ambil data kamar
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "SELECT
            id,
            room_number,
            type,
            monthly_price,
            status
         FROM rooms
         WHERE id = ?
         LIMIT 1"
    );

    $stmt->execute([$roomId]);

    $room = $stmt->fetch();

    if (!$room) {
        throw new Exception('Kamar tidak ditemukan.');
    }

    /*
    |--------------------------------------------------------------------------
    | Ambil harga saat ini
    |--------------------------------------------------------------------------
    |
    | Harga disimpan ke reservation.price_per_month
    | supaya perubahan harga kamar di masa depan tidak
    | mengubah harga booking yang sudah dibuat.
    |--------------------------------------------------------------------------
    */

    $pricePerMonth = (float) $room['monthly_price'];

    $totalPrice = $pricePerMonth * $durationMonths;

    /*
    |--------------------------------------------------------------------------
    | Mulai transaksi
    |--------------------------------------------------------------------------
    */

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Lock data kamar selama proses booking
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "SELECT
            id,
            room_number,
            type,
            monthly_price,
            status
         FROM rooms
         WHERE id = ?
         FOR UPDATE"
    );

    $stmt->execute([$roomId]);

    $lockedRoom = $stmt->fetch();

    if (!$lockedRoom) {
        throw new Exception('Kamar tidak ditemukan.');
    }

    /*
    |--------------------------------------------------------------------------
    | Cek apakah periode booking bentrok dengan reservasi lain
    |--------------------------------------------------------------------------
    |
    | Reservasi berikut dianggap masih memblokir kamar:
    |
    | pending
    | waiting_payment
    | waiting_verification
    | active
    |
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "SELECT
            id,
            start_date,
            end_date,
            status
         FROM reservations
         WHERE room_id = ?
           AND status IN (
                'pending',
                'waiting_payment',
                'waiting_verification',
                'active'
           )
           AND start_date <= ?
           AND end_date >= ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmt->execute([
        $roomId,
        $endDateString,
        $startDateString
    ]);

    $conflictingReservation = $stmt->fetch();

    if ($conflictingReservation) {
        throw new Exception(
            'Kamar sudah dipesan pada periode tersebut. Silakan pilih periode lain.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Buat nomor tagihan
    |--------------------------------------------------------------------------
    */

    $billNumber =
        'BILL-' .
        date('YmdHis') .
        '-' .
        random_int(100, 999);

    /*
    |--------------------------------------------------------------------------
    | Simpan reservasi
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "INSERT INTO reservations (
            user_id,
            room_id,
            start_date,
            end_date,
            duration_months,
            price_per_month,
            total_price,
            status,
            booking_expires_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, 'waiting_payment', ?
        )"
    );

    /*
    |--------------------------------------------------------------------------
    | Batas pembayaran booking
    |--------------------------------------------------------------------------
    |
    | Booking diberi waktu 24 jam untuk melakukan pembayaran.
    |--------------------------------------------------------------------------
    */

    $bookingExpiresAt = date(
        'Y-m-d H:i:s',
        strtotime('+24 hours')
    );

    $stmt->execute([
        $user['id'],
        $roomId,
        $startDateString,
        $endDateString,
        $durationMonths,
        $pricePerMonth,
        $totalPrice,
        $bookingExpiresAt
    ]);

    $reservationId = $pdo->lastInsertId();

    /*
    |--------------------------------------------------------------------------
    | Buat tagihan awal
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "INSERT INTO bills (
            reservation_id,
            bill_number,
            billing_period_start,
            billing_period_end,
            amount,
            due_date,
            status
        ) VALUES (
            ?, ?, ?, ?, ?, ?, 'unpaid'
        )"
    );

    $stmt->execute([
        $reservationId,
        $billNumber,
        $startDateString,
        $endDateString,
        $totalPrice,
        $bookingExpiresAt
    ]);

    /*
    |--------------------------------------------------------------------------
    | Update status kamar
    |--------------------------------------------------------------------------
    |
    | Status fisik kamar menjadi booked.
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "UPDATE rooms
         SET status = 'booked'
         WHERE id = ?"
    );

    $stmt->execute([$roomId]);

    /*
    |--------------------------------------------------------------------------
    | Buat notifikasi
    |--------------------------------------------------------------------------
    */

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

    $notificationMessage =
        'Booking kamar ' .
        $room['room_number'] .
        ' berhasil dibuat untuk periode ' .
        $startDate->format('F Y') .
        ' sampai ' .
        $endDate->format('F Y') .
        '. Silakan lakukan pembayaran sebelum batas waktu.';

    $stmt->execute([
        $user['id'],
        'Booking Berhasil',
        $notificationMessage
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
        'reservations',
        $reservationId,
        'Membuat booking kamar ' .
        $room['room_number'] .
        ' untuk periode ' .
        $startDateString .
        ' sampai ' .
        $endDateString
    ]);

    /*
    |--------------------------------------------------------------------------
    | Commit transaksi
    |--------------------------------------------------------------------------
    */

    $pdo->commit();

    /*
    |--------------------------------------------------------------------------
    | Simpan pesan sukses
    |--------------------------------------------------------------------------
    */

    $_SESSION['booking_success'] =
        'Booking kamar ' .
        $room['room_number'] .
        ' berhasil dibuat. Silakan lakukan pembayaran.';

    /*
    |--------------------------------------------------------------------------
    | Arahkan ke halaman pembayaran / dashboard
    |--------------------------------------------------------------------------
    |
    | Untuk sementara diarahkan ke dashboard tenant.
    | Nanti dapat diganti ke halaman detail pembayaran.
    |--------------------------------------------------------------------------
    */

    redirect(
    '/kost-management/public/payment.php?reservation_id=' .
    $reservationId
);

} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | Rollback jika transaksi masih aktif
    |--------------------------------------------------------------------------
    */

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    /*
    |--------------------------------------------------------------------------
    | Simpan error
    |--------------------------------------------------------------------------
    */

    $_SESSION['booking_error'] = $e->getMessage();

    /*
    |--------------------------------------------------------------------------
    | Kembali ke halaman booking
    |--------------------------------------------------------------------------
    */

    redirect(
        '/kost-management/public/booking.php?room_id=' . $roomId
    );
}
