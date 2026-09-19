
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$pageTitle = 'Admin Dashboard';

/*
|--------------------------------------------------------------------------
| Statistik Kamar
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT
        COUNT(*) AS total_rooms,
        SUM(status = 'available') AS available_rooms,
        SUM(status = 'booked') AS booked_rooms,
        SUM(status = 'occupied') AS occupied_rooms
     FROM rooms"
);

$roomStats = $stmt->fetch();

$totalRooms = (int) ($roomStats['total_rooms'] ?? 0);
$availableRooms = (int) ($roomStats['available_rooms'] ?? 0);
$bookedRooms = (int) ($roomStats['booked_rooms'] ?? 0);
$occupiedRooms = (int) ($roomStats['occupied_rooms'] ?? 0);


/*
|--------------------------------------------------------------------------
| Tenant Aktif
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM reservations
     WHERE status = 'active'"
);

$activeTenants = (int) ($stmt->fetch()['total'] ?? 0);


/*
|--------------------------------------------------------------------------
| Pembayaran Menunggu Verifikasi
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM payments
     WHERE status = 'waiting_verification'"
);

$pendingPayments = (int) ($stmt->fetch()['total'] ?? 0);


/*
|--------------------------------------------------------------------------
| Total Pendapatan Terverifikasi
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT COALESCE(SUM(amount), 0) AS total
     FROM payments
     WHERE status = 'verified'"
);

$totalIncome = (float) ($stmt->fetch()['total'] ?? 0);


/*
|--------------------------------------------------------------------------
| Kamar + Tenant Aktif / Booking Aktif
|--------------------------------------------------------------------------
|
| LEFT JOIN digunakan supaya kamar kosong tetap tampil.
|
*/

$stmt = $pdo->query(
    "SELECT
        rm.id,
        rm.room_number,
        rm.type,
        rm.monthly_price,
        rm.facilities,
        rm.status,

        r.id AS reservation_id,
        r.start_date,
        r.end_date,
        r.duration_months,
        r.status AS reservation_status,

        u.name AS tenant_name,
        u.phone AS tenant_phone

     FROM rooms rm

     LEFT JOIN reservations r
        ON r.room_id = rm.id
        AND r.status IN ('active', 'waiting_payment', 'waiting_verification')

     LEFT JOIN users u
        ON u.id = r.user_id

     ORDER BY rm.room_number ASC"
);

$rooms = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Pembayaran Terbaru yang Menunggu Verifikasi
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT
        p.id,
        p.payment_number,
        p.amount,
        p.payment_date,

        b.bill_number,

        u.name AS tenant_name,

        rm.room_number

     FROM payments p

     INNER JOIN bills b
        ON b.id = p.bill_id

     INNER JOIN reservations r
        ON r.id = b.reservation_id

     INNER JOIN users u
        ON u.id = r.user_id

     INNER JOIN rooms rm
        ON rm.id = r.room_id

     WHERE p.status = 'waiting_verification'

     ORDER BY p.created_at DESC

     LIMIT 5"
);

$latestPayments = $stmt->fetchAll();


require_once __DIR__ . '/../includes/header.php';
?>

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    <!-- Header -->

    <div class="mb-8">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <p class="text-sm font-medium text-slate-500">
                    Admin Panel
                </p>

                <h1 class="mt-1 text-2xl font-bold text-slate-900">
                    Dashboard
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Ringkasan kondisi kamar, tenant, pembayaran, dan pendapatan.
                </p>

            </div>


            <div class="flex flex-wrap gap-2">

                <a
                    href="/kost-management/admin/rooms/index.php"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    <i class="fa-solid fa-door-open"></i>
                    Kelola Kamar
                </a>


                <a
                    href="/kost-management/admin/payments/index.php"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700"
                >
                    <i class="fa-solid fa-money-check-dollar"></i>
                    Pembayaran
                    <?php if ($pendingPayments > 0): ?>
                        <span class="rounded-full bg-red-500 px-2 py-0.5 text-xs">
                            <?= $pendingPayments ?>
                        </span>
                    <?php endif; ?>
                </a>

            </div>

        </div>

    </div>


    <!-- Statistik -->

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">


        <!-- Total Kamar -->

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="flex items-center justify-between">

                <div>

                    <p class="text-sm text-slate-500">
                        Total Kamar
                    </p>

                    <p class="mt-2 text-3xl font-bold text-slate-900">
                        <?= $totalRooms ?>
                    </p>

                </div>

                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                    <i class="fa-solid fa-door-open"></i>
                </div>

            </div>

        </div>


        <!-- Tersedia -->

        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">

            <div class="flex items-center justify-between">

                <div>

                    <p class="text-sm text-emerald-700">
                        Kamar Tersedia
                    </p>

                    <p class="mt-2 text-3xl font-bold text-emerald-900">
                        <?= $availableRooms ?>
                    </p>

                </div>

                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white text-emerald-600">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

            </div>

        </div>


        <!-- Dibooking -->

        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">

            <div class="flex items-center justify-between">

                <div>

                    <p class="text-sm text-amber-700">
                        Dibooking
                    </p>

                    <p class="mt-2 text-3xl font-bold text-amber-900">
                        <?= $bookedRooms ?>
                    </p>

                </div>

                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white text-amber-600">
                    <i class="fa-solid fa-clock"></i>
                </div>

            </div>

        </div>


        <!-- Terisi -->

        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5">

            <div class="flex items-center justify-between">

                <div>

                    <p class="text-sm text-blue-700">
                        Kamar Terisi
                    </p>

                    <p class="mt-2 text-3xl font-bold text-blue-900">
                        <?= $occupiedRooms ?>
                    </p>

                </div>

                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white text-blue-600">
                    <i class="fa-solid fa-user-check"></i>
                </div>

            </div>

        </div>

    </div>


    <!-- Statistik Tambahan -->

    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">


        <!-- Tenant -->

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="flex items-center gap-4">

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-purple-100 text-purple-600">
                    <i class="fa-solid fa-users"></i>
                </div>

                <div>

                    <p class="text-sm text-slate-500">
                        Tenant Aktif
                    </p>

                    <p class="text-xl font-bold text-slate-900">
                        <?= $activeTenants ?>
                    </p>

                </div>

            </div>

        </div>


        <!-- Pending -->

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="flex items-center gap-4">

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                    <i class="fa-solid fa-money-check"></i>
                </div>

                <div>

                    <p class="text-sm text-slate-500">
                        Menunggu Verifikasi
                    </p>

                    <p class="text-xl font-bold text-slate-900">
                        <?= $pendingPayments ?>
                    </p>

                </div>

            </div>

        </div>


        <!-- Income -->

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="flex items-center gap-4">

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                    <i class="fa-solid fa-chart-line"></i>
                </div>

                <div>

                    <p class="text-sm text-slate-500">
                        Pendapatan Terverifikasi
                    </p>

                    <p class="text-xl font-bold text-slate-900">
                        <?= rupiah($totalIncome) ?>
                    </p>

                </div>

            </div>

        </div>

    </div>


    <!-- Daftar Kamar -->

    <div class="mt-8">

        <div class="mb-4 flex items-center justify-between">

            <div>

                <h2 class="text-lg font-bold text-slate-900">
                    Status Kamar
                </h2>

                <p class="text-sm text-slate-500">
                    Kondisi kamar dan tenant yang sedang terkait.
                </p>

            </div>

            <a
                href="/kost-management/admin/rooms/index.php"
                class="text-sm font-semibold text-slate-700 hover:text-slate-900"
            >
                Lihat semua
            </a>

        </div>


        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

            <?php foreach ($rooms as $room): ?>

                <?php

                $statusLabels = [
                    'available' => 'Tersedia',
                    'booked' => 'Dibooking',
                    'occupied' => 'Terisi'
                ];

                $statusClasses = [
                    'available' => 'bg-emerald-100 text-emerald-700',
                    'booked' => 'bg-amber-100 text-amber-700',
                    'occupied' => 'bg-blue-100 text-blue-700'
                ];

                $statusIcons = [
                    'available' => 'fa-circle-check',
                    'booked' => 'fa-clock',
                    'occupied' => 'fa-user-check'
                ];

                ?>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Nomor Kamar
                            </p>

                            <h3 class="mt-1 text-xl font-bold text-slate-900">
                                <?= e($room['room_number']) ?>
                            </h3>

                            <p class="text-sm text-slate-500">
                                <?= e($room['type']) ?>
                            </p>

                        </div>


                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold <?= $statusClasses[$room['status']] ?? 'bg-slate-100 text-slate-600' ?>">

                            <i class="fa-solid <?= $statusIcons[$room['status']] ?? 'fa-circle' ?>"></i>

                            <?= e($statusLabels[$room['status']] ?? $room['status']) ?>

                        </span>

                    </div>


                    <div class="mt-5 border-t border-slate-100 pt-4">

                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Tenant
                        </p>


                        <?php if ($room['tenant_name']): ?>

                            <p class="mt-1 font-semibold text-slate-900">
                                <?= e($room['tenant_name']) ?>
                            </p>

                            <?php if ($room['tenant_phone']): ?>

                                <p class="mt-1 text-xs text-slate-500">
                                    <?= e($room['tenant_phone']) ?>
                                </p>

                            <?php endif; ?>


                            <?php if ($room['start_date'] && $room['end_date']): ?>

                                <p class="mt-2 text-xs text-slate-500">

                                    <?= formatDateIndonesia($room['start_date']) ?>

                                    -

                                    <?= formatDateIndonesia($room['end_date']) ?>

                                </p>

                            <?php endif; ?>

                        <?php else: ?>

                            <p class="mt-1 text-sm text-slate-400">
                                Belum ada penyewa.
                            </p>

                        <?php endif; ?>

                    </div>


                    <div class="mt-4">

                        <a
                            href="/kost-management/admin/rooms/edit.php?id=<?= (int) $room['id'] ?>"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            <i class="fa-solid fa-pen"></i>
                            Kelola Kamar
                        </a>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </div>


    <!-- Pembayaran Terbaru -->

    <div class="mt-8">

        <div class="mb-4 flex items-center justify-between">

            <div>

                <h2 class="text-lg font-bold text-slate-900">
                    Pembayaran Menunggu Verifikasi
                </h2>

                <p class="text-sm text-slate-500">
                    Lima pembayaran terbaru yang perlu diperiksa.
                </p>

            </div>

            <a
                href="/kost-management/admin/payments/index.php"
                class="text-sm font-semibold text-slate-700 hover:text-slate-900"
            >
                Lihat semua
            </a>

        </div>


        <?php if (!$latestPayments): ?>

            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">

                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                    <i class="fa-solid fa-check"></i>
                </div>

                <p class="mt-3 font-semibold text-slate-900">
                    Tidak ada pembayaran tertunda.
                </p>

                <p class="mt-1 text-sm text-slate-500">
                    Semua pembayaran sudah diproses.
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
                                    Nomor Pembayaran
                                </th>

                                <th class="px-5 py-4">
                                    Jumlah
                                </th>

                                <th class="px-5 py-4">
                                    Aksi
                                </th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            <?php foreach ($latestPayments as $payment): ?>

                                <tr class="hover:bg-slate-50">

                                    <td class="px-5 py-4 font-semibold text-slate-900">
                                        <?= e($payment['tenant_name']) ?>
                                    </td>

                                    <td class="px-5 py-4 text-slate-600">
                                        <?= e($payment['room_number']) ?>
                                    </td>

                                    <td class="px-5 py-4 text-slate-600">
                                        <?= e($payment['payment_number']) ?>
                                    </td>

                                    <td class="px-5 py-4 font-semibold text-slate-900">
                                        <?= rupiah($payment['amount']) ?>
                                    </td>

                                    <td class="px-5 py-4">

                                        <a
                                            href="/kost-management/admin/payments/detail.php?id=<?= (int) $payment['id'] ?>"
                                            class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-700"
                                        >
                                            <i class="fa-solid fa-eye"></i>
                                            Periksa
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        <?php endif; ?>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

