
<?php

$pageTitle = 'Pembayaran';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireTenant();

$user = currentUser();

/*
|--------------------------------------------------------------------------
| Ambil reservation
|--------------------------------------------------------------------------
*/

$reservationId = filter_input(
    INPUT_GET,
    'reservation_id',
    FILTER_VALIDATE_INT
);

if (!$reservationId) {
    redirect('/kost-management/tenant/dashboard.php');
}

/*
|--------------------------------------------------------------------------
| Ambil data booking + tagihan + pembayaran
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        r.id AS reservation_id,
        r.start_date,
        r.end_date,
        r.duration_months,
        r.price_per_month,
        r.total_price,
        r.status AS reservation_status,

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

     INNER JOIN bills b
        ON b.reservation_id = r.id

     LEFT JOIN payments p
        ON p.bill_id = b.id

     WHERE r.id = ?
       AND r.user_id = ?

     ORDER BY p.created_at DESC

     LIMIT 1"
);

$stmt->execute([
    $reservationId,
    $user['id']
]);

$paymentData = $stmt->fetch();

if (!$paymentData) {
    http_response_code(404);
    exit('Data pembayaran tidak ditemukan.');
}

/*
|--------------------------------------------------------------------------
| Pesan session
|--------------------------------------------------------------------------
*/

$successMessage = $_SESSION['payment_success'] ?? null;
$errorMessage   = $_SESSION['payment_error'] ?? null;

unset($_SESSION['payment_success']);
unset($_SESSION['payment_error']);

/*
|--------------------------------------------------------------------------
| Helper status
|--------------------------------------------------------------------------
*/

function paymentStatusLabel($status)
{
    return match ($status) {
        'pending' => 'Belum Dibayar',
        'waiting_verification' => 'Menunggu Verifikasi',
        'verified' => 'Terverifikasi',
        'rejected' => 'Ditolak',
        default => ucfirst($status),
    };
}

function paymentStatusClass($status)
{
    return match ($status) {
        'pending' =>
            'bg-amber-100 text-amber-700',

        'waiting_verification' =>
            'bg-blue-100 text-blue-700',

        'verified' =>
            'bg-emerald-100 text-emerald-700',

        'rejected' =>
            'bg-red-100 text-red-700',

        default =>
            'bg-slate-100 text-slate-700',
    };
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">

    <!-- Header -->

    <div class="mb-8">

        <a
            href="/kost-management/tenant/dashboard.php"
            class="mb-4 inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900"
        >
            <i class="fa-solid fa-arrow-left"></i>
            Kembali ke Dashboard
        </a>

        <h1 class="text-2xl font-bold text-slate-900">
            Pembayaran Booking
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Lakukan pembayaran dan upload bukti pembayaran Anda.
        </p>

    </div>


    <!-- Alert -->

    <?php if ($successMessage): ?>

        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
            <div class="flex items-start gap-3">

                <i class="fa-solid fa-circle-check mt-0.5"></i>

                <span>
                    <?= e($successMessage) ?>
                </span>

            </div>
        </div>

    <?php endif; ?>


    <?php if ($errorMessage): ?>

        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <div class="flex items-start gap-3">

                <i class="fa-solid fa-circle-exclamation mt-0.5"></i>

                <span>
                    <?= e($errorMessage) ?>
                </span>

            </div>
        </div>

    <?php endif; ?>


    <div class="grid gap-6 lg:grid-cols-3">

        <!-- Detail Booking -->

        <div class="lg:col-span-2">

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <div class="mb-6 flex items-center justify-between">

                    <div>
                        <p class="text-sm text-slate-500">
                            Kamar
                        </p>

                        <h2 class="text-xl font-bold text-slate-900">
                            <?= e($paymentData['room_number']) ?>
                        </h2>
                    </div>

                    <span
                        class="rounded-full px-3 py-1 text-xs font-semibold <?= paymentStatusClass($paymentData['payment_status'] ?? 'pending') ?>"
                    >
                        <?= e(
                            paymentStatusLabel(
                                $paymentData['payment_status'] ?? 'pending'
                            )
                        ) ?>
                    </span>

                </div>


                <!-- Detail -->

                <div class="space-y-4">

                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-4">

                        <span class="text-sm text-slate-500">
                            Tipe Kamar
                        </span>

                        <span class="text-sm font-medium text-slate-900">
                            <?= e($paymentData['type']) ?>
                        </span>

                    </div>


                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-4">

                        <span class="text-sm text-slate-500">
                            Periode Sewa
                        </span>

                        <span class="text-right text-sm font-medium text-slate-900">

                            <?= date(
                                'd M Y',
                                strtotime($paymentData['start_date'])
                            ) ?>

                            -

                            <?= date(
                                'd M Y',
                                strtotime($paymentData['end_date'])
                            ) ?>

                        </span>

                    </div>


                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-4">

                        <span class="text-sm text-slate-500">
                            Durasi
                        </span>

                        <span class="text-sm font-medium text-slate-900">
                            <?= (int) $paymentData['duration_months'] ?>
                            bulan
                        </span>

                    </div>


                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-4">

                        <span class="text-sm text-slate-500">
                            Harga per Bulan
                        </span>

                        <span class="text-sm font-medium text-slate-900">
                            <?= rupiah($paymentData['price_per_month']) ?>
                        </span>

                    </div>


                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-4">

                        <span class="text-sm text-slate-500">
                            Nomor Tagihan
                        </span>

                        <span class="text-sm font-medium text-slate-900">
                            <?= e($paymentData['bill_number']) ?>
                        </span>

                    </div>


                    <div class="flex items-center justify-between gap-4">

                        <span class="text-sm text-slate-500">
                            Batas Pembayaran
                        </span>

                        <span class="text-sm font-semibold text-red-600">
                            <?= date(
                                'd M Y H:i',
                                strtotime($paymentData['due_date'])
                            ) ?>
                        </span>

                    </div>

                </div>

            </div>

        </div>


        <!-- Total -->

        <div>

            <div class="rounded-2xl bg-slate-900 p-6 text-white shadow-sm">

                <p class="text-sm text-slate-400">
                    Total Pembayaran
                </p>

                <p class="mt-2 text-3xl font-bold">
                    <?= rupiah($paymentData['total_price']) ?>
                </p>

                <div class="mt-5 border-t border-slate-700 pt-5">

                    <div class="flex items-center justify-between text-sm">

                        <span class="text-slate-400">
                            <?= (int) $paymentData['duration_months'] ?>
                            bulan
                        </span>

                        <span>
                            <?= rupiah($paymentData['price_per_month']) ?>
                            / bulan
                        </span>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- Upload -->

    <?php
    $paymentStatus = $paymentData['payment_status'] ?? null;

    $canUpload =
        !$paymentStatus ||
        $paymentStatus === 'pending' ||
        $paymentStatus === 'rejected';
    ?>


    <?php if ($canUpload): ?>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="mb-6">

                <h2 class="text-lg font-bold text-slate-900">
                    Upload Bukti Pembayaran
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Upload bukti transfer untuk diproses oleh admin.
                </p>

            </div>


            <?php if (
                $paymentStatus === 'rejected' &&
                !empty($paymentData['rejection_reason'])
            ): ?>

                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">

                    <div class="flex items-start gap-3">

                        <i class="fa-solid fa-circle-xmark mt-0.5 text-red-600"></i>

                        <div>

                            <p class="font-semibold text-red-700">
                                Pembayaran ditolak
                            </p>

                            <p class="mt-1 text-sm text-red-600">
                                <?= e($paymentData['rejection_reason']) ?>
                            </p>

                        </div>

                    </div>

                </div>

            <?php endif; ?>


           <form
                action="/kost-management/actions/payment.php"
                method="POST"
                enctype="multipart/form-data"
                class="space-y-5"
            >

                <input
                    type="hidden"
                    name="bill_id"
                    value="<?= (int) $paymentData['bill_id'] ?>"
                >

                <input
                    type="hidden"
                    name="reservation_id"
                    value="<?= (int) $paymentData['reservation_id'] ?>"
                >


                <div>

                    <label
                        for="amount"
                        class="mb-2 block text-sm font-semibold text-slate-700"
                    >
                        Jumlah Pembayaran
                    </label>

                    <input
                        type="number"
                        id="amount"
                        name="amount"
                        value="<?= e($paymentData['bill_amount']) ?>"
                        min="1"
                        step="0.01"
                        required
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-slate-900 focus:ring-2 focus:ring-slate-200"
                    >

                    <p class="mt-1 text-xs text-slate-500">
                        Pastikan jumlah pembayaran sesuai dengan total tagihan.
                    </p>

                </div>


                <div>

                    <label
                        for="proof_file"
                        class="mb-2 block text-sm font-semibold text-slate-700"
                    >
                        Bukti Pembayaran
                    </label>

                    <input
                        type="file"
                        id="proof_file"
                        name="proof_file"
                        accept=".jpg,.jpeg,.png,.webp"
                        required
                        class="block w-full cursor-pointer rounded-xl border border-slate-300 bg-white text-sm text-slate-600 file:mr-4 file:border-0 file:bg-slate-100 file:px-4 file:py-3 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200"
                    >

                    <p class="mt-2 text-xs text-slate-500">
                        Format: JPG, JPEG, PNG, atau WEBP. Maksimal 2 MB.
                    </p>

                </div>


                <button
                    type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-700"
                >
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    Kirim Bukti Pembayaran
                </button>

            </form>

        </div>


    <?php else: ?>

        <div class="mt-6 rounded-2xl border border-blue-200 bg-blue-50 p-5">

            <div class="flex items-start gap-3">

                <i class="fa-solid fa-clock mt-0.5 text-blue-600"></i>

                <div>

                    <p class="font-semibold text-blue-800">
                        Pembayaran sedang diproses
                    </p>

                    <p class="mt-1 text-sm text-blue-700">
                        Bukti pembayaran Anda sudah dikirim dan sedang menunggu verifikasi admin.
                    </p>

                </div>

            </div>

        </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>