php
<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

$pageTitle = 'Manajemen Tenant';


/*
|--------------------------------------------------------------------------
| Tenant Aktif
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT
        r.id AS reservation_id,

        r.start_date,
        r.end_date,
        r.duration_months,
        r.price_per_month,
        r.total_price,
        r.status,

        u.id AS user_id,
        u.name AS tenant_name,
        u.email,
        u.phone,

        rm.id AS room_id,
        rm.room_number,
        rm.type AS room_type

     FROM reservations r

     INNER JOIN users u
        ON u.id = r.user_id

     INNER JOIN rooms rm
        ON rm.id = r.room_id

     WHERE r.status = 'active'

     ORDER BY r.end_date ASC"
);

$activeTenants = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Reservation Berjalan / Belum Aktif
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT
        r.id AS reservation_id,

        r.start_date,
        r.end_date,
        r.duration_months,
        r.price_per_month,
        r.total_price,
        r.status,
        r.booking_expires_at,

        u.id AS user_id,
        u.name AS tenant_name,
        u.email,
        u.phone,

        rm.id AS room_id,
        rm.room_number,
        rm.type AS room_type,

        b.bill_number,
        b.amount AS bill_amount,
        b.status AS bill_status,

        p.payment_number,
        p.status AS payment_status

     FROM reservations r

     INNER JOIN users u
        ON u.id = r.user_id

     INNER JOIN rooms rm
        ON rm.id = r.room_id

     LEFT JOIN bills b
        ON b.reservation_id = r.id

     LEFT JOIN payments p
        ON p.bill_id = b.id
        AND p.id = (
            SELECT MAX(p2.id)
            FROM payments p2
            WHERE p2.bill_id = b.id
        )

     WHERE r.status IN (
        'waiting_payment',
        'waiting_verification'
     )

     ORDER BY r.created_at DESC"
);

$pendingReservations = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Helper Status
|--------------------------------------------------------------------------
*/

$reservationStatusLabels = [
    'waiting_payment' => 'Menunggu Pembayaran',
    'waiting_verification' => 'Menunggu Verifikasi',
    'active' => 'Aktif',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan',
    'rejected' => 'Ditolak',
    'pending' => 'Pending'
];

$reservationStatusClasses = [
    'waiting_payment' => 'bg-amber-100 text-amber-700',
    'waiting_verification' => 'bg-blue-100 text-blue-700',
    'active' => 'bg-emerald-100 text-emerald-700',
    'completed' => 'bg-slate-100 text-slate-600',
    'cancelled' => 'bg-red-100 text-red-700',
    'rejected' => 'bg-red-100 text-red-700',
    'pending' => 'bg-slate-100 text-slate-600'
];


require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    <!-- Header -->

    <div class="mb-8">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <a
                    href="/kost-management/admin/dashboard.php"
                    class="mb-3 inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-900"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                    Dashboard
                </a>

                <h1 class="text-2xl font-bold text-slate-900">
                    Manajemen Tenant
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Kelola tenant aktif dan reservation yang masih berjalan.
                </p>

            </div>

        </div>

    </div>


    <?php if (isset($_SESSION['tenant_success'])): ?>

        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">

            <div class="flex items-center gap-2">

                <i class="fa-solid fa-circle-check"></i>

                <?= e($_SESSION['tenant_success']) ?>

            </div>

        </div>

        <?php unset($_SESSION['tenant_success']); ?>

    <?php endif; ?>


    <?php if (isset($_SESSION['tenant_error'])): ?>

        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">

            <div class="flex items-center gap-2">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?= e($_SESSION['tenant_error']) ?>

            </div>

        </div>

        <?php unset($_SESSION['tenant_error']); ?>

    <?php endif; ?>


    <!-- ========================================================= -->
    <!-- TENANT AKTIF -->
    <!-- ========================================================= -->

    <section>

        <div class="mb-4 flex items-center justify-between">

            <div>

                <h2 class="text-lg font-bold text-slate-900">
                    Tenant Aktif
                </h2>

                <p class="text-sm text-slate-500">
                    Tenant yang memiliki reservation aktif.
                </p>

            </div>

            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                <?= count($activeTenants) ?> Tenant
            </span>

        </div>


        <?php if (!$activeTenants): ?>

            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">

                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">

                    <i class="fa-solid fa-users text-lg"></i>

                </div>

                <p class="mt-3 font-semibold text-slate-900">
                    Belum ada tenant aktif.
                </p>

                <p class="mt-1 text-sm text-slate-500">
                    Tenant akan muncul setelah pembayaran booking diverifikasi.
                </p>

            </div>

        <?php else: ?>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="overflow-x-auto">

                    <table class="min-w-full text-sm">

                        <thead class="border-b border-slate-200 bg-slate-50">

                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">

                                <th class="px-5 py-4">
                                    Tenant
                                </th>

                                <th class="px-5 py-4">
                                    Kamar
                                </th>

                                <th class="px-5 py-4">
                                    Periode
                                </th>

                                <th class="px-5 py-4">
                                    Durasi
                                </th>

                                <th class="px-5 py-4">
                                    Total
                                </th>

                                <th class="px-5 py-4 text-right">
                                    Aksi
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100">

                            <?php foreach ($activeTenants as $tenant): ?>

                                <tr class="hover:bg-slate-50">

                                    <td class="px-5 py-4">

                                        <p class="font-semibold text-slate-900">
                                            <?= e($tenant['tenant_name']) ?>
                                        </p>

                                        <p class="mt-1 text-xs text-slate-500">
                                            <?= e($tenant['email']) ?>
                                        </p>

                                    </td>


                                    <td class="px-5 py-4">

                                        <p class="font-semibold text-slate-900">
                                            <?= e($tenant['room_number']) ?>
                                        </p>

                                        <p class="text-xs text-slate-500">
                                            <?= e($tenant['room_type']) ?>
                                        </p>

                                    </td>


                                    <td class="px-5 py-4">

                                        <p class="text-slate-700">

                                            <?= formatDateIndonesia($tenant['start_date']) ?>

                                            -

                                            <?= formatDateIndonesia($tenant['end_date']) ?>

                                        </p>

                                    </td>


                                    <td class="px-5 py-4 text-slate-700">

                                        <?= (int) $tenant['duration_months'] ?>
                                        bulan

                                    </td>


                                    <td class="px-5 py-4">

                                        <p class="font-semibold text-slate-900">
                                            <?= rupiah($tenant['total_price']) ?>
                                        </p>

                                        <p class="mt-1 text-xs text-slate-500">
                                            <?= rupiah($tenant['price_per_month']) ?>/bulan
                                        </p>

                                    </td>


                                    <td class="px-5 py-4 text-right">

                                        <a
                                            href="/kost-management/admin/tenants/detail.php?id=<?= (int) $tenant['reservation_id'] ?>"
                                            class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white hover:bg-slate-700"
                                        >
                                            <i class="fa-solid fa-eye"></i>
                                            Detail
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        <?php endif; ?>

    </section>


    <!-- ========================================================= -->
    <!-- RESERVATION BELUM AKTIF -->
    <!-- ========================================================= -->

    <section class="mt-10">

        <div class="mb-4 flex items-center justify-between">

            <div>

                <h2 class="text-lg font-bold text-slate-900">
                    Reservation Berjalan
                </h2>

                <p class="text-sm text-slate-500">
                    Booking yang masih menunggu pembayaran atau verifikasi.
                </p>

            </div>

            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                <?= count($pendingReservations) ?> Reservation
            </span>

        </div>


        <?php if (!$pendingReservations): ?>

            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">

                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">

                    <i class="fa-solid fa-calendar-xmark text-lg"></i>

                </div>

                <p class="mt-3 font-semibold text-slate-900">
                    Tidak ada reservation pending.
                </p>

            </div>

        <?php else: ?>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">

                <?php foreach ($pendingReservations as $reservation): ?>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <div class="flex items-start justify-between gap-3">

                            <div>

                                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                    Kamar
                                </p>

                                <h3 class="mt-1 text-xl font-bold text-slate-900">
                                    <?= e($reservation['room_number']) ?>
                                </h3>

                            </div>


                            <span class="rounded-full px-3 py-1.5 text-xs font-semibold <?= $reservationStatusClasses[$reservation['status']] ?? 'bg-slate-100 text-slate-600' ?>">

                                <?= e(
                                    $reservationStatusLabels[$reservation['status']]
                                    ?? $reservation['status']
                                ) ?>

                            </span>

                        </div>


                        <div class="mt-5">

                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Calon Tenant
                            </p>

                            <p class="mt-1 font-semibold text-slate-900">
                                <?= e($reservation['tenant_name']) ?>
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                <?= e($reservation['email']) ?>
                            </p>

                        </div>


                        <div class="mt-5 grid grid-cols-2 gap-3">

                            <div class="rounded-xl bg-slate-50 p-3">

                                <p class="text-xs text-slate-400">
                                    Mulai
                                </p>

                                <p class="mt-1 text-sm font-semibold text-slate-800">
                                    <?= formatDateIndonesia($reservation['start_date']) ?>
                                </p>

                            </div>


                            <div class="rounded-xl bg-slate-50 p-3">

                                <p class="text-xs text-slate-400">
                                    Selesai
                                </p>

                                <p class="mt-1 text-sm font-semibold text-slate-800">
                                    <?= formatDateIndonesia($reservation['end_date']) ?>
                                </p>

                            </div>

                        </div>


                        <div class="mt-4 flex items-center justify-between text-sm">

                            <span class="text-slate-500">
                                <?= (int) $reservation['duration_months'] ?> bulan
                            </span>

                            <strong class="text-slate-900">
                                <?= rupiah($reservation['total_price']) ?>
                            </strong>

                        </div>


                        <?php if ($reservation['payment_status']): ?>

                            <div class="mt-4 border-t border-slate-100 pt-4">

                                <p class="text-xs text-slate-400">
                                    Status Pembayaran
                                </p>

                                <p class="mt-1 text-sm font-semibold text-slate-700">

                                    <?php if ($reservation['payment_status'] === 'waiting_verification'): ?>

                                        Menunggu Verifikasi

                                    <?php elseif ($reservation['payment_status'] === 'rejected'): ?>

                                        Ditolak

                                    <?php elseif ($reservation['payment_status'] === 'verified'): ?>

                                        Terverifikasi

                                    <?php else: ?>

                                        <?= e($reservation['payment_status']) ?>

                                    <?php endif; ?>

                                </p>

                            </div>

                        <?php endif; ?>


                        <a
                            href="/kost-management/admin/tenants/detail.php?id=<?= (int) $reservation['reservation_id'] ?>"
                            class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            <i class="fa-solid fa-eye"></i>
                            Lihat Detail
                        </a>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

