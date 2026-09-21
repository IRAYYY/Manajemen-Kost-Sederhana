
<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

$reservationId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$reservationId) {
    redirect('/kost-management/admin/tenants/index.php');
}


/*
|--------------------------------------------------------------------------
| Reservation + Tenant + Room
|--------------------------------------------------------------------------
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
        r.created_at,

        u.id AS user_id,
        u.name AS tenant_name,
        u.email AS tenant_email,
        u.phone AS tenant_phone,
        u.status AS tenant_status,

        rm.id AS room_id,
        rm.room_number,
        rm.type AS room_type,
        rm.monthly_price,
        rm.facilities,
        rm.status AS room_status

     FROM reservations r

     INNER JOIN users u
        ON u.id = r.user_id

     INNER JOIN rooms rm
        ON rm.id = r.room_id

     WHERE r.id = ?

     LIMIT 1"
);

$stmt->execute([
    $reservationId
]);

$reservation = $stmt->fetch();

if (!$reservation) {

    $_SESSION['tenant_error'] =
        'Reservation tidak ditemukan.';

    redirect(
        '/kost-management/admin/tenants/index.php'
    );
}


/*
|--------------------------------------------------------------------------
| Bill
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        id,
        bill_number,
        billing_period_start,
        billing_period_end,
        amount,
        due_date,
        status,
        created_at

     FROM bills

     WHERE reservation_id = ?

     ORDER BY created_at ASC"
);

$stmt->execute([
    $reservationId
]);

$bills = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Payment History
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        p.id,
        p.payment_number,
        p.amount,
        p.payment_date,
        p.proof_file,
        p.status,
        p.rejection_reason,
        p.verified_at,

        b.bill_number

     FROM payments p

     INNER JOIN bills b
        ON b.id = p.bill_id

     WHERE b.reservation_id = ?

     ORDER BY p.created_at DESC"
);

$stmt->execute([
    $reservationId
]);

$payments = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Helper
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

$paymentStatusLabels = [
    'pending' => 'Pending',
    'waiting_verification' => 'Menunggu Verifikasi',
    'verified' => 'Terverifikasi',
    'rejected' => 'Ditolak'
];

$paymentStatusClasses = [
    'pending' => 'bg-slate-100 text-slate-600',
    'waiting_verification' => 'bg-amber-100 text-amber-700',
    'verified' => 'bg-emerald-100 text-emerald-700',
    'rejected' => 'bg-red-100 text-red-700'
];

$billStatusLabels = [
    'unpaid' => 'Belum Lunas',
    'waiting_verification' => 'Menunggu Verifikasi',
    'paid' => 'Lunas',
    'overdue' => 'Terlambat',
    'cancelled' => 'Dibatalkan'
];

$billStatusClasses = [
    'unpaid' => 'bg-amber-100 text-amber-700',
    'waiting_verification' => 'bg-blue-100 text-blue-700',
    'paid' => 'bg-emerald-100 text-emerald-700',
    'overdue' => 'bg-red-100 text-red-700',
    'cancelled' => 'bg-slate-100 text-slate-600'
];


$pageTitle =
    'Detail Tenant - ' .
    $reservation['tenant_name'];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">

    <!-- Header -->

    <div class="mb-8">

        <a
            href="/kost-management/admin/tenants/index.php"
            class="mb-4 inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-900"
        >
            <i class="fa-solid fa-arrow-left"></i>
            Kembali ke Tenant
        </a>


        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">

            <div>

                <p class="text-sm text-slate-500">
                    Detail Reservation
                </p>

                <h1 class="mt-1 text-2xl font-bold text-slate-900">
                    <?= e($reservation['tenant_name']) ?>
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Kamar <?= e($reservation['room_number']) ?>
                </p>

            </div>


            <span class="inline-flex w-fit items-center rounded-full px-3 py-1.5 text-xs font-semibold <?= $reservationStatusClasses[$reservation['status']] ?? 'bg-slate-100 text-slate-600' ?>">

                <?= e(
                    $reservationStatusLabels[$reservation['status']]
                    ?? $reservation['status']
                ) ?>

            </span>

        </div>

    </div>


    <?php if (isset($_SESSION['tenant_error'])): ?>

        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <?= e($_SESSION['tenant_error']) ?>
        </div>

        <?php unset($_SESSION['tenant_error']); ?>

    <?php endif; ?>


    <!-- Informasi Utama -->

    <div class="grid gap-6 lg:grid-cols-3">

        <!-- Tenant -->

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex items-center gap-3">

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                    <i class="fa-solid fa-user"></i>
                </div>

                <div>

                    <h2 class="font-bold text-slate-900">
                        Tenant
                    </h2>

                    <p class="text-xs text-slate-500">
                        Informasi penyewa
                    </p>

                </div>

            </div>


            <div class="mt-6 space-y-4">

                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Nama
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">
                        <?= e($reservation['tenant_name']) ?>
                    </p>

                </div>


                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Email
                    </p>

                    <p class="mt-1 break-all text-sm text-slate-700">
                        <?= e($reservation['tenant_email']) ?>
                    </p>

                </div>


                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Telepon
                    </p>

                    <p class="mt-1 text-sm text-slate-700">
                        <?= e($reservation['tenant_phone'] ?: '-') ?>
                    </p>

                </div>

            </div>

        </div>


        <!-- Room -->

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex items-center gap-3">

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-purple-100 text-purple-600">
                    <i class="fa-solid fa-door-open"></i>
                </div>

                <div>

                    <h2 class="font-bold text-slate-900">
                        Kamar
                    </h2>

                    <p class="text-xs text-slate-500">
                        Informasi kamar
                    </p>

                </div>

            </div>


            <div class="mt-6 space-y-4">

                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Nomor
                    </p>

                    <p class="mt-1 text-lg font-bold text-slate-900">
                        <?= e($reservation['room_number']) ?>
                    </p>

                </div>


                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Tipe
                    </p>

                    <p class="mt-1 text-sm text-slate-700">
                        <?= e($reservation['room_type']) ?>
                    </p>

                </div>


                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Harga Sekarang
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">
                        <?= rupiah($reservation['monthly_price']) ?>
                    </p>

                </div>

            </div>

        </div>


        <!-- Reservation -->

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex items-center gap-3">

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>

                <div>

                    <h2 class="font-bold text-slate-900">
                        Reservation
                    </h2>

                    <p class="text-xs text-slate-500">
                        Periode sewa
                    </p>

                </div>

            </div>


            <div class="mt-6 space-y-4">

                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Mulai
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">
                        <?= formatDateIndonesia($reservation['start_date']) ?>
                    </p>

                </div>


                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Selesai
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">
                        <?= formatDateIndonesia($reservation['end_date']) ?>
                    </p>

                </div>


                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Durasi
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">
                        <?= (int) $reservation['duration_months'] ?> bulan
                    </p>

                </div>

            </div>

        </div>

    </div>


    <!-- Nilai Reservation -->

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

        <h2 class="text-lg font-bold text-slate-900">
            Nilai Reservation
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Nilai ini adalah nilai yang tersimpan ketika booking dilakukan.
        </p>


        <div class="mt-5 grid gap-4 sm:grid-cols-3">

            <div class="rounded-xl bg-slate-50 p-4">

                <p class="text-xs text-slate-400">
                    Harga / Bulan Saat Booking
                </p>

                <p class="mt-1 text-lg font-bold text-slate-900">
                    <?= rupiah($reservation['price_per_month']) ?>
                </p>

            </div>


            <div class="rounded-xl bg-slate-50 p-4">

                <p class="text-xs text-slate-400">
                    Durasi
                </p>

                <p class="mt-1 text-lg font-bold text-slate-900">
                    <?= (int) $reservation['duration_months'] ?> bulan
                </p>

            </div>


            <div class="rounded-xl bg-slate-900 p-4">

                <p class="text-xs text-slate-400">
                    Total Reservation
                </p>

                <p class="mt-1 text-lg font-bold text-white">
                    <?= rupiah($reservation['total_price']) ?>
                </p>

            </div>

        </div>

    </div>


    <!-- Tagihan -->

    <div class="mt-6">

        <div class="mb-4">

            <h2 class="text-lg font-bold text-slate-900">
                Tagihan
            </h2>

            <p class="text-sm text-slate-500">
                Riwayat tagihan yang terkait dengan reservation.
            </p>

        </div>


        <?php if (!$bills): ?>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">
                Belum ada tagihan.
            </div>

        <?php else: ?>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="overflow-x-auto">

                    <table class="min-w-full text-sm">

                        <thead class="border-b border-slate-200 bg-slate-50">

                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">

                                <th class="px-5 py-4">
                                    Nomor
                                </th>

                                <th class="px-5 py-4">
                                    Periode
                                </th>

                                <th class="px-5 py-4">
                                    Jumlah
                                </th>

                                <th class="px-5 py-4">
                                    Jatuh Tempo
                                </th>

                                <th class="px-5 py-4">
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100">

                            <?php foreach ($bills as $bill): ?>

                                <tr>

                                    <td class="px-5 py-4 font-semibold text-slate-900">
                                        <?= e($bill['bill_number']) ?>
                                    </td>

                                    <td class="px-5 py-4 text-slate-600">

                                        <?= formatDateIndonesia($bill['billing_period_start']) ?>

                                        -

                                        <?= formatDateIndonesia($bill['billing_period_end']) ?>

                                    </td>

                                    <td class="px-5 py-4 font-semibold text-slate-900">
                                        <?= rupiah($bill['amount']) ?>
                                    </td>

                                    <td class="px-5 py-4 text-slate-600">
                                        <?= formatDateIndonesia($bill['due_date']) ?>
                                    </td>

                                    <td class="px-5 py-4">

                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold <?= $billStatusClasses[$bill['status']] ?? 'bg-slate-100 text-slate-600' ?>">

                                            <?= e(
                                                $billStatusLabels[$bill['status']]
                                                ?? $bill['status']
                                            ) ?>

                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        <?php endif; ?>

    </div>


    <!-- Payment History -->

    <div class="mt-8">

        <div class="mb-4">

            <h2 class="text-lg font-bold text-slate-900">
                Riwayat Pembayaran
            </h2>

            <p class="text-sm text-slate-500">
                Semua percobaan pembayaran tetap disimpan sebagai histori.
            </p>

        </div>


        <?php if (!$payments): ?>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">
                Belum ada pembayaran.
            </div>

        <?php else: ?>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="overflow-x-auto">

                    <table class="min-w-full text-sm">

                        <thead class="border-b border-slate-200 bg-slate-50">

                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">

                                <th class="px-5 py-4">
                                    Pembayaran
                                </th>

                                <th class="px-5 py-4">
                                    Tagihan
                                </th>

                                <th class="px-5 py-4">
                                    Jumlah
                                </th>

                                <th class="px-5 py-4">
                                    Tanggal
                                </th>

                                <th class="px-5 py-4">
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100">

                            <?php foreach ($payments as $payment): ?>

                                <tr>

                                    <td class="px-5 py-4">

                                        <p class="font-semibold text-slate-900">
                                            <?= e($payment['payment_number']) ?>
                                        </p>

                                    </td>


                                    <td class="px-5 py-4 text-slate-600">
                                        <?= e($payment['bill_number']) ?>
                                    </td>


                                    <td class="px-5 py-4 font-semibold text-slate-900">
                                        <?= rupiah($payment['amount']) ?>
                                    </td>


                                    <td class="px-5 py-4 text-slate-600">

                                        <?= date(
                                            'd/m/Y H:i',
                                            strtotime($payment['payment_date'])
                                        ) ?>

                                    </td>


                                    <td class="px-5 py-4">

                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold <?= $paymentStatusClasses[$payment['status']] ?? 'bg-slate-100 text-slate-600' ?>">

                                            <?= e(
                                                $paymentStatusLabels[$payment['status']]
                                                ?? $payment['status']
                                            ) ?>

                                        </span>

                                        <?php if ($payment['rejection_reason']): ?>

                                            <p class="mt-2 max-w-xs text-xs text-red-600">
                                                <?= e($payment['rejection_reason']) ?>
                                            </p>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        <?php endif; ?>

    </div>


    <!-- Akhiri Sewa -->

    <?php if ($reservation['status'] === 'active'): ?>

        <div class="mt-8 rounded-2xl border border-red-200 bg-red-50 p-6">

            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">

                <div>

                    <h2 class="font-bold text-red-900">
                        Akhiri Masa Sewa
                    </h2>

                    <p class="mt-1 max-w-2xl text-sm text-red-700">
                        Gunakan tindakan ini ketika tenant benar-benar telah selesai menggunakan kamar.
                        Data reservation dan pembayaran tidak akan dihapus.
                    </p>

                </div>


                <form
                    action="/kost-management/admin/tenants/selesai-sewa.php"
                    method="POST"
                    onsubmit="return confirm('Yakin ingin mengakhiri masa sewa tenant ini? Tindakan ini akan mengubah reservation menjadi selesai dan kamar menjadi tersedia.')"
                >

                    <input
                        type="hidden"
                        name="reservation_id"
                        value="<?= (int) $reservation['id'] ?>"
                    >

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white hover:bg-red-700 sm:w-auto"
                    >
                        <i class="fa-solid fa-door-open"></i>
                        Akhiri Masa Sewa
                    </button>

                </form>

            </div>

        </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

