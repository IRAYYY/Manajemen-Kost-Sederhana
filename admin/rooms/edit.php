
<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

$roomId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$roomId) {
    redirect('/kost-management/admin/rooms/index.php');
}


$stmt = $pdo->prepare(
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
        u.email AS tenant_email,
        u.phone AS tenant_phone

     FROM rooms rm

     LEFT JOIN reservations r
        ON r.room_id = rm.id
        AND r.status IN ('active', 'waiting_payment', 'waiting_verification')

     LEFT JOIN users u
        ON u.id = r.user_id

     WHERE rm.id = ?

     LIMIT 1"
);

$stmt->execute([
    $roomId
]);

$room = $stmt->fetch();

if (!$room) {
    $_SESSION['room_error'] =
        'Kamar tidak ditemukan.';

    redirect(
        '/kost-management/admin/rooms/index.php'
    );
}


$pageTitle =
    'Edit Kamar ' . $room['room_number'];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">

    <a
        href="/kost-management/admin/rooms/index.php"
        class="mb-5 inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-900"
    >
        <i class="fa-solid fa-arrow-left"></i>
        Kembali ke Kamar
    </a>


    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-100 p-6 sm:p-8">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                <div>

                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        Edit Kamar
                    </p>

                    <h1 class="mt-1 text-2xl font-bold text-slate-900">
                        <?= e($room['room_number']) ?>
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        <?= e($room['type']) ?>
                    </p>

                </div>


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

                ?>

                <span class="inline-flex w-fit items-center rounded-full px-3 py-1.5 text-xs font-semibold <?= $statusClasses[$room['status']] ?? 'bg-slate-100 text-slate-600' ?>">
                    <?= e($statusLabels[$room['status']] ?? $room['status']) ?>
                </span>

            </div>

        </div>


        <?php if (isset($_SESSION['room_error'])): ?>

            <div class="mx-6 mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 sm:mx-8">

                <?= e($_SESSION['room_error']) ?>

            </div>

            <?php unset($_SESSION['room_error']); ?>

        <?php endif; ?>


        <!-- Tenant Saat Ini -->

        <div class="border-b border-slate-100 p-6 sm:p-8">

            <h2 class="text-lg font-bold text-slate-900">
                Penyewa Saat Ini
            </h2>


            <?php if ($room['tenant_name']): ?>

                <div class="mt-4 rounded-xl bg-blue-50 p-4">

                    <div class="flex items-start gap-3">

                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                            <i class="fa-solid fa-user"></i>
                        </div>

                        <div>

                            <p class="font-semibold text-slate-900">
                                <?= e($room['tenant_name']) ?>
                            </p>

                            <p class="mt-1 text-sm text-slate-600">
                                <?= e($room['tenant_email']) ?>
                            </p>

                            <?php if ($room['tenant_phone']): ?>

                                <p class="mt-1 text-sm text-slate-600">
                                    <?= e($room['tenant_phone']) ?>
                                </p>

                            <?php endif; ?>

                            <?php if ($room['start_date'] && $room['end_date']): ?>

                                <p class="mt-2 text-xs text-slate-500">

                                    Periode:

                                    <?= formatDateIndonesia($room['start_date']) ?>

                                    -

                                    <?= formatDateIndonesia($room['end_date']) ?>

                                </p>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            <?php else: ?>

                <div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-500">

                    <i class="fa-solid fa-circle-info mr-1"></i>

                    Belum ada penyewa yang aktif pada kamar ini.

                </div>

            <?php endif; ?>

        </div>


        <!-- Form -->

        <form
            action="/kost-management/admin/rooms/update.php"
            method="POST"
            class="p-6 sm:p-8"
        >

            <input
                type="hidden"
                name="id"
                value="<?= (int) $room['id'] ?>"
            >


            <div class="space-y-5">

                <!-- Nomor -->

                <div>

                    <label
                        for="room_number"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Nomor Kamar
                    </label>

                    <input
                        type="text"
                        id="room_number"
                        name="room_number"
                        value="<?= e($room['room_number']) ?>"
                        required
                        maxlength="20"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-100"
                    >

                </div>


                <!-- Type -->

                <div>

                    <label
                        for="type"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Tipe Kamar
                    </label>

                    <input
                        type="text"
                        id="type"
                        name="type"
                        value="<?= e($room['type']) ?>"
                        required
                        maxlength="50"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-100"
                    >

                </div>


                <!-- Harga -->

                <div>

                    <label
                        for="monthly_price"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Harga Per Bulan
                    </label>

                    <div class="relative">

                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-medium text-slate-400">
                            Rp
                        </span>

                        <input
                            type="number"
                            id="monthly_price"
                            name="monthly_price"
                            value="<?= e($room['monthly_price']) ?>"
                            required
                            min="0"
                            step="1000"
                            class="w-full rounded-xl border border-slate-300 py-3 pl-12 pr-4 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-100"
                        >

                    </div>

                </div>


                <!-- Fasilitas -->

                <div>

                    <label
                        for="facilities"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Fasilitas
                    </label>

                    <textarea
                        id="facilities"
                        name="facilities"
                        rows="4"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-100"
                    ><?= e($room['facilities']) ?></textarea>

                </div>

            </div>


            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

                <a
                    href="/kost-management/admin/rooms/index.php"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-700"
                >
                    <i class="fa-solid fa-save"></i>
                    Simpan Perubahan
                </button>

            </div>

        </form>

    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
