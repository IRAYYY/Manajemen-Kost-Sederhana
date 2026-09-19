
<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

$pageTitle = 'Manajemen Kamar';


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

        u.name AS tenant_name,
        u.email AS tenant_email,
        u.phone AS tenant_phone

     FROM rooms rm

     LEFT JOIN reservations r
        ON r.room_id = rm.id
        AND r.status = 'active'

     LEFT JOIN users u
        ON u.id = r.user_id

     ORDER BY rm.room_number ASC"
);

$rooms = $stmt->fetchAll();


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
                    Manajemen Kamar
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Kelola data kamar, harga, fasilitas, status, dan tenant.
                </p>

            </div>


            <a
                href="/kost-management/admin/rooms/create.php"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-700"
            >
                <i class="fa-solid fa-plus"></i>
                Tambah Kamar
            </a>

        </div>

    </div>


    <?php if (isset($_SESSION['room_success'])): ?>

        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">

            <div class="flex items-center gap-2">

                <i class="fa-solid fa-circle-check"></i>

                <span>
                    <?= e($_SESSION['room_success']) ?>
                </span>

            </div>

        </div>

        <?php unset($_SESSION['room_success']); ?>

    <?php endif; ?>


    <?php if (isset($_SESSION['room_error'])): ?>

        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">

            <div class="flex items-center gap-2">

                <i class="fa-solid fa-circle-exclamation"></i>

                <span>
                    <?= e($_SESSION['room_error']) ?>
                </span>

            </div>

        </div>

        <?php unset($_SESSION['room_error']); ?>

    <?php endif; ?>


    <?php if (!$rooms): ?>

        <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">

            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">

                <i class="fa-solid fa-door-open text-xl"></i>

            </div>

            <h2 class="mt-4 text-lg font-semibold text-slate-900">
                Belum Ada Kamar
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Tambahkan kamar pertama untuk mulai mengelola kost.
            </p>

            <a
                href="/kost-management/admin/rooms/create.php"
                class="mt-5 inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700"
            >
                <i class="fa-solid fa-plus"></i>
                Tambah Kamar
            </a>

        </div>

    <?php else: ?>

        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">

            <?php foreach ($rooms as $room): ?>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <!-- Card Header -->

                    <div class="border-b border-slate-100 p-5">

                        <div class="flex items-start justify-between gap-4">

                            <div>

                                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                    Nomor Kamar
                                </p>

                                <h2 class="mt-1 text-2xl font-bold text-slate-900">
                                    <?= e($room['room_number']) ?>
                                </h2>

                                <p class="mt-1 text-sm text-slate-500">
                                    <?= e($room['type']) ?>
                                </p>

                            </div>


                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold <?= $statusClasses[$room['status']] ?? 'bg-slate-100 text-slate-600' ?>">

                                <i class="fa-solid <?= $statusIcons[$room['status']] ?? 'fa-circle' ?>"></i>

                                <?= e($statusLabels[$room['status']] ?? $room['status']) ?>

                            </span>

                        </div>

                    </div>


                    <!-- Card Body -->

                    <div class="p-5">

                        <div>

                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Harga Per Bulan
                            </p>

                            <p class="mt-1 text-lg font-bold text-slate-900">
                                <?= rupiah($room['monthly_price']) ?>
                            </p>

                        </div>


                        <div class="mt-5">

                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Fasilitas
                            </p>

                            <p class="mt-1 text-sm leading-6 text-slate-600">
                                <?= e($room['facilities'] ?: 'Belum ada fasilitas.') ?>
                            </p>

                        </div>


                        <div class="mt-5 rounded-xl bg-slate-50 p-4">

                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Penyewa Saat Ini
                            </p>


                            <?php if ($room['tenant_name']): ?>

                                <div class="mt-2 flex items-start gap-3">

                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                        <i class="fa-solid fa-user"></i>
                                    </div>

                                    <div class="min-w-0">

                                        <p class="font-semibold text-slate-900">
                                            <?= e($room['tenant_name']) ?>
                                        </p>

                                        <?php if ($room['tenant_phone']): ?>

                                            <p class="mt-0.5 text-xs text-slate-500">
                                                <?= e($room['tenant_phone']) ?>
                                            </p>

                                        <?php endif; ?>

                                        <p class="mt-1 text-xs text-slate-500">

                                            <?= formatDateIndonesia($room['start_date']) ?>

                                            -

                                            <?= formatDateIndonesia($room['end_date']) ?>

                                        </p>

                                    </div>

                                </div>

                            <?php else: ?>

                                <p class="mt-2 text-sm text-slate-400">
                                    Belum ada penyewa.
                                </p>

                            <?php endif; ?>

                        </div>

                    </div>


                    <!-- Card Footer -->

                    <div class="flex gap-2 border-t border-slate-100 bg-slate-50 p-4">

                        <a
                            href="/kost-management/admin/rooms/edit.php?id=<?= (int) $room['id'] ?>"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            <i class="fa-solid fa-pen"></i>
                            Edit
                        </a>


                        <?php if (!$room['tenant_name'] && $room['status'] === 'available'): ?>

                            <form
                                action="/kost-management/admin/rooms/delete.php"
                                method="POST"
                                class="flex-1"
                                onsubmit="return confirm('Yakin ingin menghapus kamar ini?')"
                            >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int) $room['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50"
                                >
                                    <i class="fa-solid fa-trash"></i>
                                    Hapus
                                </button>

                            </form>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
