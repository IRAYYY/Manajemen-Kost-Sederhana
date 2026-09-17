<?php

$pageTitle = 'Detail Kamar';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

/*
|--------------------------------------------------------------------------
| Validasi ID
|--------------------------------------------------------------------------
*/

$roomId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$roomId) {
    redirect('/kost-management/public/rooms.php');
}


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
        facilities,
        status
     FROM rooms
     WHERE id = ?
     LIMIT 1"
);

$stmt->execute([$roomId]);

$room = $stmt->fetch();

if (!$room) {
    http_response_code(404);
    exit('Kamar tidak ditemukan.');
}


/*
|--------------------------------------------------------------------------
| Ambil tenant aktif
|--------------------------------------------------------------------------
|
| Satu kamar hanya boleh mempunyai satu reservasi aktif.
|
*/

$stmt = $pdo->prepare(
    "SELECT
        r.id,
        r.start_date,
        r.end_date,
        r.duration_months,
        u.name,
        u.email,
        u.phone
     FROM reservations r
     INNER JOIN users u
        ON u.id = r.user_id
     WHERE r.room_id = ?
       AND r.status = 'active'
     ORDER BY r.start_date DESC
     LIMIT 1"
);

$stmt->execute([$roomId]);

$currentTenant = $stmt->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="bg-slate-900">

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

        <a
            href="/kost-management/public/rooms.php"
            class="inline-flex items-center gap-2 text-sm text-slate-300 transition hover:text-white"
        >
            <i class="fa-solid fa-arrow-left"></i>
            Kembali ke daftar kamar
        </a>

        <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <p class="text-sm text-slate-400">
                    Detail Kamar
                </p>

                <h1 class="mt-1 text-4xl font-bold text-white">
                    Kamar <?= e($room['room_number']) ?>
                </h1>

            </div>

            <span
                class="inline-flex w-fit rounded-full px-4 py-2 text-sm font-semibold <?= roomStatusClass($room['status']) ?>"
            >
                <?= e(roomStatusLabel($room['status'])) ?>
            </span>

        </div>

    </div>

</section>


<section class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">

    <div class="grid gap-8 lg:grid-cols-3">

        <!-- Informasi Kamar -->

        <div class="lg:col-span-2">

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <div class="flex items-center gap-4">

                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100">
                        <i class="fa-solid fa-door-open text-2xl text-slate-700"></i>
                    </div>

                    <div>

                        <p class="text-sm text-slate-500">
                            Nomor Kamar
                        </p>

                        <h2 class="text-2xl font-bold text-slate-900">
                            <?= e($room['room_number']) ?>
                        </h2>

                    </div>

                </div>


                <div class="mt-8 grid gap-6 sm:grid-cols-2">

                    <div class="rounded-xl bg-slate-50 p-5">

                        <div class="flex items-center gap-3">

                            <i class="fa-solid fa-layer-group text-slate-500"></i>

                            <div>

                                <p class="text-xs text-slate-500">
                                    Tipe Kamar
                                </p>

                                <p class="mt-1 font-semibold text-slate-900">
                                    <?= e($room['type']) ?>
                                </p>

                            </div>

                        </div>

                    </div>


                    <div class="rounded-xl bg-slate-50 p-5">

                        <div class="flex items-center gap-3">

                            <i class="fa-solid fa-money-bill-wave text-slate-500"></i>

                            <div>

                                <p class="text-xs text-slate-500">
                                    Harga Per Bulan
                                </p>

                                <p class="mt-1 font-semibold text-slate-900">
                                    <?= rupiah($room['monthly_price']) ?>
                                </p>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Fasilitas -->

                <div class="mt-8">

                    <h3 class="text-lg font-semibold text-slate-900">
                        Fasilitas
                    </h3>

                    <?php if (!empty($room['facilities'])): ?>

                        <div class="mt-3 rounded-xl bg-slate-50 p-5">

                            <p class="text-sm leading-7 text-slate-700">
                                <?= nl2br(e($room['facilities'])) ?>
                            </p>

                        </div>

                    <?php else: ?>

                        <p class="mt-3 text-sm text-slate-500">
                            Tidak ada informasi fasilitas.
                        </p>

                    <?php endif; ?>

                </div>

            </div>

        </div>


        <!-- Status / Booking -->

        <div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h2 class="text-lg font-bold text-slate-900">
                    Status Kamar
                </h2>


                <div class="mt-5 rounded-xl p-4 <?= roomStatusClass($room['status']) ?>">

                    <div class="flex items-center gap-3">

                        <?php if ($room['status'] === 'available'): ?>

                            <i class="fa-solid fa-circle-check"></i>

                        <?php elseif ($room['status'] === 'booked'): ?>

                            <i class="fa-solid fa-clock"></i>

                        <?php else: ?>

                            <i class="fa-solid fa-user"></i>

                        <?php endif; ?>

                        <span class="font-semibold">
                            <?= e(roomStatusLabel($room['status'])) ?>
                        </span>

                    </div>

                </div>


                <?php if ($room['status'] === 'available'): ?>

                    <div class="mt-6">

                        <p class="text-sm leading-6 text-slate-500">
                            Kamar ini tersedia dan dapat dipesan.
                            Pada tahap berikutnya Anda dapat menentukan
                            durasi sewa dan melakukan pembayaran.
                        </p>

                        <?php if (isLoggedIn() && currentUser()['role'] === 'tenant'): ?>

                            <a
    href="/kost-management/public/booking.php?room_id=<?= (int) $room['id'] ?>"
    class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-700"
>
    <i class="fa-solid fa-calendar-check"></i>
    Pesan Kamar
</a>

                        <?php elseif (!isLoggedIn()): ?>

                            <a
                                href="/kost-management/public/login.php"
                                class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-700"
                            >
                                <i class="fa-solid fa-right-to-bracket"></i>
                                Login untuk Memesan
                            </a>

                        <?php endif; ?>

                    </div>

                <?php elseif ($room['status'] === 'booked'): ?>

                    <div class="mt-6">

                        <p class="text-sm leading-6 text-slate-500">
                            Kamar sedang dalam proses pemesanan
                            dan belum tersedia untuk pemesanan lain.
                        </p>

                    </div>

                <?php elseif ($room['status'] === 'occupied'): ?>

                    <div class="mt-6">

                        <p class="text-sm leading-6 text-slate-500">
                            Kamar sedang ditempati oleh penyewa.
                        </p>

                    </div>

                <?php endif; ?>

            </div>


            <?php if ($currentTenant): ?>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <div class="flex items-center gap-3">

                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100">
                            <i class="fa-solid fa-user text-slate-600"></i>
                        </div>

                        <div>

                            <p class="text-xs text-slate-500">
                                Penyewa Saat Ini
                            </p>

                            <h2 class="font-bold text-slate-900">
                                <?= e($currentTenant['name']) ?>
                            </h2>

                        </div>

                    </div>


                    <div class="mt-5 space-y-4 text-sm">

                        <div>
                            <p class="text-slate-500">
                                Email
                            </p>

                            <p class="mt-1 font-medium text-slate-800">
                                <?= e($currentTenant['email']) ?>
                            </p>
                        </div>


                        <?php if (!empty($currentTenant['phone'])): ?>

                            <div>
                                <p class="text-slate-500">
                                    Nomor HP
                                </p>

                                <p class="mt-1 font-medium text-slate-800">
                                    <?= e($currentTenant['phone']) ?>
                                </p>
                            </div>

                        <?php endif; ?>


                        <div class="grid grid-cols-2 gap-4">

                            <div>

                                <p class="text-slate-500">
                                    Mulai
                                </p>

                                <p class="mt-1 font-medium text-slate-800">
                                    <?= formatDateIndonesia($currentTenant['start_date']) ?>
                                </p>

                            </div>

                            <div>

                                <p class="text-slate-500">
                                    Berakhir
                                </p>

                                <p class="mt-1 font-medium text-slate-800">
                                    <?= formatDateIndonesia($currentTenant['end_date']) ?>
                                </p>

                            </div>

                        </div>


                        <div>

                            <p class="text-slate-500">
                                Durasi
                            </p>

                            <p class="mt-1 font-medium text-slate-800">
                                <?= (int) $currentTenant['duration_months'] ?> bulan
                            </p>

                        </div>

                    </div>

                </div>

            <?php elseif ($room['status'] === 'occupied'): ?>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <div class="flex items-center gap-3">

                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100">
                            <i class="fa-solid fa-user-slash text-slate-500"></i>
                        </div>

                        <div>

                            <h2 class="font-bold text-slate-900">
                                Belum ada data penyewa
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Data reservasi aktif tidak ditemukan.
                            </p>

                        </div>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>