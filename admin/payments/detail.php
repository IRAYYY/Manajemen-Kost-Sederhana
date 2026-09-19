
<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

$paymentId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$paymentId) {
    redirect('/kost-management/admin/payments/index.php');
}


$stmt = $pdo->prepare(
    "SELECT
        p.id,
        p.payment_number,
        p.amount AS payment_amount,
        p.payment_date,
        p.proof_file,
        p.status,
        p.rejection_reason,

        b.id AS bill_id,
        b.bill_number,
        b.amount AS bill_amount,
        b.due_date,
        b.status AS bill_status,

        r.id AS reservation_id,
        r.start_date,
        r.end_date,
        r.duration_months,
        r.price_per_month,
        r.total_price,
        r.status AS reservation_status,

        u.id AS user_id,
        u.name AS tenant_name,
        u.email AS tenant_email,
        u.phone AS tenant_phone,

        rm.id AS room_id,
        rm.room_number,
        rm.type AS room_type,
        rm.monthly_price

     FROM payments p

     INNER JOIN bills b
        ON b.id = p.bill_id

     INNER JOIN reservations r
        ON r.id = b.reservation_id

     INNER JOIN users u
        ON u.id = r.user_id

     INNER JOIN rooms rm
        ON rm.id = r.room_id

     WHERE p.id = ?

     LIMIT 1"
);

$stmt->execute([$paymentId]);

$payment = $stmt->fetch();

if (!$payment) {
    $_SESSION['payment_error'] = 'Data pembayaran tidak ditemukan.';

    redirect(
        '/kost-management/admin/payments/index.php'
    );
}


$pageTitle = 'Detail Pembayaran';

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">

    <!-- Header -->

    <div class="mb-8">

        <a
            href="/kost-management/admin/payments/index.php"
            class="mb-4 inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900"
        >
            <i class="fa-solid fa-arrow-left"></i>
            Kembali ke pembayaran
        </a>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">

            <div>

                <h1 class="text-2xl font-bold text-slate-900">
                    Detail Pembayaran
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Periksa informasi dan bukti pembayaran sebelum mengambil tindakan.
                </p>

            </div>

            <?php if ($payment['status'] === 'waiting_verification'): ?>

                <span class="inline-flex w-fit items-center gap-2 rounded-full bg-amber-100 px-3 py-1.5 text-xs font-semibold text-amber-700">
                    <i class="fa-solid fa-clock"></i>
                    Menunggu Verifikasi
                </span>

            <?php elseif ($payment['status'] === 'verified'): ?>

                <span class="inline-flex w-fit items-center gap-2 rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                    <i class="fa-solid fa-circle-check"></i>
                    Terverifikasi
                </span>

            <?php elseif ($payment['status'] === 'rejected'): ?>

                <span class="inline-flex w-fit items-center gap-2 rounded-full bg-red-100 px-3 py-1.5 text-xs font-semibold text-red-700">
                    <i class="fa-solid fa-circle-xmark"></i>
                    Ditolak
                </span>

            <?php endif; ?>

        </div>

    </div>


    <!-- Main Grid -->

    <div class="grid gap-6 lg:grid-cols-3">

        <!-- Information -->

        <div class="space-y-6 lg:col-span-2">

            <!-- Tenant -->

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h2 class="mb-5 flex items-center gap-2 text-lg font-bold text-slate-900">

                    <i class="fa-solid fa-user text-slate-500"></i>

                    Data Tenant

                </h2>


                <div class="grid gap-4 sm:grid-cols-2">

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Nama
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= e($payment['tenant_name']) ?>
                        </p>
                    </div>


                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Email
                        </p>

                        <p class="mt-1 text-slate-700">
                            <?= e($payment['tenant_email']) ?>
                        </p>
                    </div>


                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            No. Telepon
                        </p>

                        <p class="mt-1 text-slate-700">
                            <?= e($payment['tenant_phone'] ?: '-') ?>
                        </p>
                    </div>

                </div>

            </div>


            <!-- Reservation -->

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h2 class="mb-5 flex items-center gap-2 text-lg font-bold text-slate-900">

                    <i class="fa-solid fa-house text-slate-500"></i>

                    Data Sewa

                </h2>


                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Kamar
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= e($payment['room_number']) ?>
                        </p>

                        <p class="text-xs text-slate-500">
                            <?= e($payment['room_type']) ?>
                        </p>
                    </div>


                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Mulai Sewa
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= formatDateIndonesia($payment['start_date']) ?>
                        </p>
                    </div>


                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Selesai Sewa
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= formatDateIndonesia($payment['end_date']) ?>
                        </p>
                    </div>


                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Durasi
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= (int) $payment['duration_months'] ?> bulan
                        </p>
                    </div>


                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Harga / Bulan
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= rupiah($payment['price_per_month']) ?>
                        </p>
                    </div>


                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Total Sewa
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= rupiah($payment['total_price']) ?>
                        </p>
                    </div>

                </div>

            </div>


            <!-- Payment -->

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h2 class="mb-5 flex items-center gap-2 text-lg font-bold text-slate-900">

                    <i class="fa-solid fa-money-bill-wave text-slate-500"></i>

                    Data Pembayaran

                </h2>


                <div class="grid gap-5 sm:grid-cols-2">

                    <div>

                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Nomor Pembayaran
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= e($payment['payment_number']) ?>
                        </p>

                    </div>


                    <div>

                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Nomor Tagihan
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= e($payment['bill_number']) ?>
                        </p>

                    </div>


                    <div>

                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Jumlah Tagihan
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= rupiah($payment['bill_amount']) ?>
                        </p>

                    </div>


                    <div>

                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Jumlah Dibayar
                        </p>

                        <p class="mt-1 text-lg font-bold text-slate-900">
                            <?= rupiah($payment['payment_amount']) ?>
                        </p>

                    </div>


                    <div>

                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Tanggal Pembayaran
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= date(
                                'd/m/Y H:i',
                                strtotime($payment['payment_date'])
                            ) ?>
                        </p>

                    </div>


                    <div>

                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Jatuh Tempo
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= formatDateIndonesia($payment['due_date']) ?>
                        </p>

                    </div>

                </div>

            </div>

        </div>


        <!-- Proof + Action -->

        <div class="space-y-6">

            <!-- Proof -->

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-slate-900">

                    <i class="fa-solid fa-image text-slate-500"></i>

                    Bukti Pembayaran

                </h2>


                <?php if ($payment['proof_file']): ?>

                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50">

                        <img
                            src="/kost-management/uploads/payment-proofs/<?= e($payment['proof_file']) ?>"
                            alt="Bukti pembayaran"
                            class="w-full object-contain"
                        >

                    </div>

                    <a
                        href="/kost-management/uploads/payment-proofs/<?= e($payment['proof_file']) ?>"
                        target="_blank"
                        class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        <i class="fa-solid fa-up-right-from-square"></i>
                        Buka Gambar
                    </a>

                <?php else: ?>

                    <div class="rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">
                        Bukti pembayaran tidak tersedia.
                    </div>

                <?php endif; ?>

            </div>


            <?php if ($payment['status'] === 'waiting_verification'): ?>

                <!-- Verify -->

                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6">

                    <h2 class="font-bold text-emerald-900">
                        Verifikasi Pembayaran
                    </h2>

                    <p class="mt-1 text-sm text-emerald-700">
                        Pastikan jumlah dan bukti pembayaran sudah benar sebelum menyetujui.
                    </p>


                    <form
                        action="/kost-management/admin/payments/verify.php"
                        method="POST"
                        class="mt-5"
                    >

                        <input
                            type="hidden"
                            name="payment_id"
                            value="<?= (int) $payment['id'] ?>"
                        >

                        <button
                            type="submit"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 font-semibold text-white hover:bg-emerald-700"
                            onclick="return confirm('Yakin ingin memverifikasi pembayaran ini?')"
                        >
                            <i class="fa-solid fa-check"></i>
                            Verifikasi Pembayaran
                        </button>

                    </form>

                </div>


                <!-- Reject -->

                <div class="rounded-2xl border border-red-200 bg-red-50 p-6">

                    <h2 class="font-bold text-red-900">
                        Tolak Pembayaran
                    </h2>

                    <p class="mt-1 text-sm text-red-700">
                        Berikan alasan agar tenant mengetahui apa yang perlu diperbaiki.
                    </p>


                    <form
                        action="/kost-management/admin/payments/reject.php"
                        method="POST"
                        class="mt-5 space-y-4"
                    >

                        <input
                            type="hidden"
                            name="payment_id"
                            value="<?= (int) $payment['id'] ?>"
                        >


                        <div>

                            <label
                                for="rejection_reason"
                                class="mb-2 block text-sm font-medium text-slate-700"
                            >
                                Alasan Penolakan
                            </label>

                            <textarea
                                id="rejection_reason"
                                name="rejection_reason"
                                rows="4"
                                required
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-red-500 focus:ring-2 focus:ring-red-100"
                                placeholder="Contoh: Bukti pembayaran tidak jelas atau jumlah pembayaran tidak sesuai."
                            ></textarea>

                        </div>


                        <button
                            type="submit"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-3 font-semibold text-white hover:bg-red-700"
                            onclick="return confirm('Yakin ingin menolak pembayaran ini?')"
                        >
                            <i class="fa-solid fa-xmark"></i>
                            Tolak Pembayaran
                        </button>

                    </form>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
