
<?php

$pageTitle = 'Dashboard Tenant';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireTenant();

$user = currentUser();


/*
|--------------------------------------------------------------------------
| Ambil reservasi aktif / sedang diproses
|--------------------------------------------------------------------------
|
| Dashboard mengambil reservasi terbaru milik tenant.
|
*/

$stmt = $pdo->prepare(
    "SELECT
        r.id,
        r.start_date,
        r.end_date,
        r.duration_months,
        r.price_per_month,
        r.total_price,
        r.status,
        r.booking_expires_at,

        rm.room_number,
        rm.type,

        b.id AS bill_id,
        b.bill_number,
        b.amount AS bill_amount,
        b.due_date,
        b.status AS bill_status,

        p.id AS payment_id,
        p.payment_number,
        p.amount AS payment_amount,
        p.payment_date,
        p.proof_file,
        p.status AS payment_status,
        p.rejection_reason

     FROM reservations r

     INNER JOIN rooms rm
        ON rm.id = r.room_id

     LEFT JOIN bills b
        ON b.reservation_id = r.id

     LEFT JOIN payments p
        ON p.bill_id = b.id

     WHERE r.user_id = ?
       AND r.status IN (
            'pending',
            'waiting_payment',
            'waiting_verification',
            'active'
       )

     ORDER BY r.created_at DESC,
              p.created_at DESC

     LIMIT 1"
);

$stmt->execute([
    $user['id']
]);

$reservation = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Helper status reservasi
|--------------------------------------------------------------------------
*/

function reservationStatusLabel($status)
{
    return match ($status) {

        'pending' =>
            'Menunggu Proses',

        'waiting_payment' =>
            'Menunggu Pembayaran',

        'waiting_verification' =>
            'Menunggu Verifikasi',

        'active' =>
            'Aktif',

        default =>
            ucwords(
                str_replace(
                    '_',
                    ' ',
                    $status
                )
            ),
    };
}


function reservationStatusClass($status)
{
    return match ($status) {

        'pending' =>
            'bg-slate-100 text-slate-700',

        'waiting_payment' =>
            'bg-yellow-100 text-yellow-700',

        'waiting_verification' =>
            'bg-blue-100 text-blue-700',

        'active' =>
            'bg-green-100 text-green-700',

        default =>
            'bg-slate-100 text-slate-700',
    };
}


require_once __DIR__ . '/../includes/header.php';
?>

<section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

    <!-- Header -->

    <div class="mb-8">

        <p class="text-sm font-medium text-slate-500">
            Dashboard Tenant
        </p>

        <h1 class="mt-1 text-3xl font-bold text-slate-900">
            Halo, <?= e($user['name']) ?> 👋
        </h1>

        <p class="mt-2 text-slate-500">
            Pantau kamar, reservasi, dan pembayaran Anda.
        </p>

    </div>


    <!-- Success message -->

    <?php if (isset($_GET['success'])): ?>

        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">

            <i class="fa-solid fa-circle-check mr-2"></i>

            <?= e($_GET['success']) ?>

        </div>

    <?php endif; ?>


    <!-- Error message -->

    <?php if (isset($_GET['error'])): ?>

        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">

            <i class="fa-solid fa-circle-exclamation mr-2"></i>

            <?= e($_GET['error']) ?>

        </div>

    <?php endif; ?>


    <?php if ($reservation): ?>

        <!-- Reservation Card -->

        <div class="mb-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <!-- Header Reservation -->

            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                <div>

                    <p class="text-sm text-slate-500">
                        Reservasi Anda
                    </p>

                    <h2 class="mt-1 text-2xl font-bold text-slate-900">
                        Kamar <?= e($reservation['room_number']) ?>
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        <?= e($reservation['type']) ?>
                    </p>

                </div>


                <span
                    class="rounded-full px-3 py-1 text-xs font-semibold <?= reservationStatusClass($reservation['status']) ?>"
                >
                    <?= e(
                        reservationStatusLabel(
                            $reservation['status']
                        )
                    ) ?>
                </span>

            </div>


            <!-- Detail Reservation -->

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                <!-- Durasi -->

                <div class="rounded-xl bg-slate-50 p-4">

                    <p class="text-xs text-slate-500">
                        Durasi
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">
                        <?= (int) $reservation['duration_months'] ?>
                        bulan
                    </p>

                </div>


                <!-- Mulai -->

                <div class="rounded-xl bg-slate-50 p-4">

                    <p class="text-xs text-slate-500">
                        Mulai
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">
                        <?= formatDateIndonesia(
                            $reservation['start_date']
                        ) ?>
                    </p>

                </div>


                <!-- Berakhir -->

                <div class="rounded-xl bg-slate-50 p-4">

                    <p class="text-xs text-slate-500">
                        Berakhir
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">
                        <?= formatDateIndonesia(
                            $reservation['end_date']
                        ) ?>
                    </p>

                </div>


                <!-- Total -->

                <div class="rounded-xl bg-slate-50 p-4">

                    <p class="text-xs text-slate-500">
                        Total
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">
                        <?= rupiah(
                            $reservation['total_price']
                        ) ?>
                    </p>

                </div>

            </div>


            <!-- =====================================================
                 WAITING PAYMENT
                 ===================================================== -->

            <?php if (
                $reservation['status'] === 'waiting_payment'
            ): ?>

                <div class="mt-6 rounded-xl border border-yellow-200 bg-yellow-50 p-4">

                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                        <div class="flex gap-3">

                            <i class="fa-solid fa-clock mt-0.5 text-yellow-600"></i>

                            <div>

                                <p class="font-semibold text-yellow-800">
                                    Menunggu Pembayaran
                                </p>

                                <p class="mt-1 text-sm leading-6 text-yellow-700">
                                    Silakan lakukan pembayaran
                                    sebelum batas waktu pemesanan
                                    berakhir.
                                </p>


                                <?php if (
                                    $reservation['booking_expires_at']
                                ): ?>

                                    <p class="mt-2 text-sm font-semibold text-yellow-800">

                                        Batas waktu:

                                        <?= date(
                                            'd/m/Y H:i',
                                            strtotime(
                                                $reservation[
                                                    'booking_expires_at'
                                                ]
                                            )
                                        ) ?>

                                    </p>

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- Tombol pembayaran -->

                        <?php if ($reservation['bill_id']): ?>

                            <a
                                href="/kost-management/public/payment.php?reservation_id=<?= (int) $reservation['id'] ?>"
                                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-700"
                            >
                                <i class="fa-solid fa-credit-card"></i>
                                Bayar Sekarang
                            </a>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endif; ?>


            <!-- =====================================================
                 WAITING VERIFICATION
                 ===================================================== -->

            <?php if (
                $reservation['status'] === 'waiting_verification'
            ): ?>

                <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4">

                    <div class="flex items-start gap-3">

                        <i class="fa-solid fa-clock mt-0.5 text-blue-600"></i>

                        <div>

                            <p class="font-semibold text-blue-800">
                                Pembayaran Sedang Diverifikasi
                            </p>

                            <p class="mt-1 text-sm leading-6 text-blue-700">
                                Bukti pembayaran Anda sudah dikirim.
                                Silakan tunggu verifikasi dari admin.
                            </p>

                        </div>

                    </div>

                </div>

            <?php endif; ?>


            <!-- =====================================================
                 PAYMENT REJECTED
                 ===================================================== -->

            <?php if (
                $reservation['payment_status'] === 'rejected'
            ): ?>

                <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4">

                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                        <div class="flex gap-3">

                            <i class="fa-solid fa-circle-xmark mt-0.5 text-red-600"></i>

                            <div>

                                <p class="font-semibold text-red-800">
                                    Pembayaran Ditolak
                                </p>

                                <?php if (
                                    !empty(
                                        $reservation[
                                            'rejection_reason'
                                        ]
                                    )
                                ): ?>

                                    <p class="mt-1 text-sm leading-6 text-red-700">

                                        Alasan:

                                        <?= e(
                                            $reservation[
                                                'rejection_reason'
                                            ]
                                        ) ?>

                                    </p>

                                <?php else: ?>

                                    <p class="mt-1 text-sm text-red-700">
                                        Silakan upload kembali bukti pembayaran.
                                    </p>

                                <?php endif; ?>

                            </div>

                        </div>


                        <a
                            href="/kost-management/public/payment.php?reservation_id=<?= (int) $reservation['id'] ?>"
                            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white hover:bg-red-700"
                        >
                            <i class="fa-solid fa-upload"></i>
                            Upload Ulang
                        </a>

                    </div>

                </div>

            <?php endif; ?>


            <!-- =====================================================
                 ACTIVE
                 ===================================================== -->

            <?php if (
                $reservation['status'] === 'active'
            ): ?>

                <div class="mt-6 rounded-xl border border-green-200 bg-green-50 p-4">

                    <div class="flex items-start gap-3">

                        <i class="fa-solid fa-circle-check mt-0.5 text-green-600"></i>

                        <div>

                            <p class="font-semibold text-green-800">
                                Kamar Anda Aktif
                            </p>

                            <p class="mt-1 text-sm leading-6 text-green-700">
                                Pembayaran telah diverifikasi.
                                Reservasi kamar Anda sekarang aktif.
                            </p>

                        </div>

                    </div>

                </div>

            <?php endif; ?>


            <!-- Detail pembayaran -->

            <?php if ($reservation['bill_id']): ?>

                <div class="mt-6 border-t border-slate-100 pt-6">

                    <div class="grid gap-4 sm:grid-cols-2">

                        <div>

                            <p class="text-xs text-slate-500">
                                Nomor Tagihan
                            </p>

                            <p class="mt-1 text-sm font-semibold text-slate-900">
                                <?= e(
                                    $reservation['bill_number']
                                ) ?>
                            </p>

                        </div>


                        <div>

                            <p class="text-xs text-slate-500">
                                Status Tagihan
                            </p>

                            <p class="mt-1 text-sm font-semibold text-slate-900">
                                <?= e(
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $reservation[
                                                'bill_status'
                                            ]
                                        )
                                    )
                                ) ?>
                            </p>

                        </div>

                    </div>

                </div>

            <?php endif; ?>

        </div>


    <?php else: ?>

        <!-- Tidak ada reservasi -->

        <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">

            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">

                <i class="fa-solid fa-door-open text-xl text-slate-500"></i>

            </div>

            <h2 class="mt-4 text-xl font-bold text-slate-900">
                Belum Ada Reservasi
            </h2>

            <p class="mt-2 text-sm text-slate-500">
                Anda belum memiliki reservasi yang sedang diproses.
            </p>

            <a
                href="/kost-management/public/rooms.php"
                class="mt-6 inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-700"
            >
                <i class="fa-solid fa-door-open"></i>
                Lihat Kamar
            </a>

        </div>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

