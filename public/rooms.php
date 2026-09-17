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
        monthly_price,
        facilities,
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
                    class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg"
                >

                    <!-- Header Card -->

                    <div class="flex items-start justify-between border-b border-slate-100 p-6">

                        <div>

                            <div class="flex items-center gap-2">

                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100">
                                    <i class="fa-solid fa-door-open text-lg text-slate-700"></i>
                                </div>

                                <div>

                                    <p class="text-xs text-slate-500">
                                        Nomor Kamar
                                    </p>

                                    <h3 class="text-xl font-bold text-slate-900">
                                        <?= e($room['room_number']) ?>
                                    </h3>

                                </div>

                            </div>

                        </div>


                        <span
                            class="rounded-full px-3 py-1 text-xs font-semibold <?= roomStatusClass($room['status']) ?>"
                        >
                            <?= e(roomStatusLabel($room['status'])) ?>
                        </span>

                    </div>


                    <!-- Body -->

                    <div class="p-6">

                        <p class="text-sm text-slate-500">
                            Tipe Kamar
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= e($room['type']) ?>
                        </p>


                        <div class="mt-5">

                            <p class="text-sm text-slate-500">
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

                            <div class="mt-5">

                                <p class="mb-2 text-sm text-slate-500">
                                    Fasilitas
                                </p>

                                <p class="line-clamp-2 text-sm leading-6 text-slate-700">
                                    <?= e($room['facilities']) ?>
                                </p>

                            </div>

                        <?php endif; ?>


                        <a
                            href="/kost-management/public/room-detail.php?id=<?= (int) $room['id'] ?>"
                            class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:bg-slate-900 hover:text-white"
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