<?php

$pageTitle = 'Daftar Kamar';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

/*
|--------------------------------------------------------------------------
| Ambil semua kamar
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT
        id,
        room_number,
        type,
        capacity,
        monthly_price,
        facilities,
        photo,
        status
     FROM rooms
     ORDER BY room_number ASC"
);

$rooms = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="bg-slate-900">

    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">

        <div class="max-w-3xl">

            <p class="text-sm font-semibold uppercase tracking-wider text-slate-400">
                Informasi Kost
            </p>

            <h1 class="mt-2 text-4xl font-bold tracking-tight text-white sm:text-5xl">
                Pilih Kamar Anda
            </h1>

            <p class="mt-4 text-lg leading-8 text-slate-300">
                Lihat informasi kamar, fasilitas, harga sewa,
                dan status kamar sebelum melakukan pemesanan.
            </p>

        </div>

    </div>

</section>


<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">

    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">

        <div>
            <p class="text-sm font-medium text-slate-500">
                Daftar Kamar
            </p>

            <h2 class="mt-1 text-2xl font-bold text-slate-900">
                Kamar Kost
            </h2>
        </div>

        <div class="text-sm text-slate-500">
            <?= count($rooms) ?> kamar tersedia dalam sistem
        </div>

    </div>


    <?php if (!$rooms): ?>

        <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center">

            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                <i class="fa-solid fa-door-closed text-xl text-slate-500"></i>
            </div>

            <h3 class="mt-4 text-lg font-semibold text-slate-900">
                Belum ada kamar
            </h3>

            <p class="mt-2 text-sm text-slate-500">
                Data kamar belum tersedia.
            </p>

        </div>

    <?php else: ?>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">

            <?php foreach ($rooms as $room): ?>

                <article
                    class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg flex flex-col justify-between"
                >

                    <div>
                        <!-- Photo Banner -->
                        <div class="relative h-48 w-full bg-slate-100 overflow-hidden border-b border-slate-100">
                            <?php if (!empty($room['photo'])): ?>
                                <img
                                    src="/kost-management/uploads/rooms/<?= e($room['photo']) ?>"
                                    alt="Foto Kamar <?= e($room['room_number']) ?>"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                >
                            <?php else: ?>
                                <div class="flex h-full w-full flex-col items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200 text-slate-400">
                                    <i class="fa-solid fa-door-open text-4xl"></i>
                                    <span class="mt-2 text-xs font-medium">Foto belum tersedia</span>
                                </div>
                            <?php endif; ?>

                            <span
                                class="absolute top-3 right-3 rounded-full px-3 py-1 text-xs font-semibold shadow-sm <?= roomStatusClass($room['status']) ?>"
                            >
                                <?= e(roomStatusLabel($room['status'])) ?>
                            </span>
                        </div>

                        <!-- Header Card -->
                        <div class="border-b border-slate-100 p-5">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-xs text-slate-500">
                                        Nomor Kamar
                                    </p>
                                    <h3 class="text-2xl font-bold text-slate-900">
                                        <?= e($room['room_number']) ?>
                                    </h3>
                                </div>

                                <div class="flex flex-col items-end gap-1.5">
                                    <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                        <i class="fa-solid fa-layer-group text-slate-400"></i>
                                        <?= e(roomTypeLabel($room['type'])) ?>
                                    </span>
                                    <span class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                        <i class="fa-solid fa-users text-blue-400"></i>
                                        <?= (int) ($room['capacity'] ?? 1) ?> Orang
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="p-5">
                            <div>
                                <p class="text-xs text-slate-500">
                                    Harga Sewa
                                </p>
                                <p class="mt-1 text-xl font-bold text-slate-900">
                                    <?= rupiah($room['monthly_price']) ?>
                                    <span class="text-sm font-normal text-slate-500">
                                        / bulan
                                    </span>
                                </p>
                            </div>

                            <?php if (!empty($room['facilities'])): ?>
                                <div class="mt-4">
                                    <p class="mb-1 text-xs text-slate-500">
                                        Fasilitas
                                    </p>
                                    <p class="line-clamp-2 text-sm leading-6 text-slate-700">
                                        <?= e($room['facilities']) ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="p-5 pt-0">
                        <a
                            href="/kost-management/public/room-detail.php?id=<?= (int) $room['id'] ?>"
                            class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:bg-slate-900 hover:text-white"
                        >
                            <i class="fa-solid fa-arrow-right"></i>
                            Lihat Detail
                        </a>
                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>