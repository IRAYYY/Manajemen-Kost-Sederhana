
<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

$pageTitle = 'Verifikasi Pembayaran';

$stmt = $pdo->prepare(
    "SELECT
        p.id,
        p.payment_number,
        p.amount,
        p.payment_date,
        p.proof_file,
        p.status,

        b.id AS bill_id,
        b.bill_number,
        b.amount AS bill_amount,

        r.id AS reservation_id,
        r.start_date,
        r.end_date,
        r.duration_months,

        u.id AS user_id,
        u.name AS tenant_name,
        u.email AS tenant_email,
        u.phone AS tenant_phone,

        rm.room_number,
        rm.type AS room_type

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

     ORDER BY p.created_at ASC"
);

$stmt->execute();

$payments = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    <div class="mb-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Verifikasi Pembayaran
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Periksa bukti pembayaran tenant sebelum menyetujui atau menolaknya.
                </p>
            </div>

            <a
                href="/kost-management/admin/dashboard.php"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                <i class="fa-solid fa-arrow-left"></i>
                Dashboard
            </a>

        </div>
    </div>


    <?php if (!$payments): ?>

        <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">

            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                <i class="fa-solid fa-circle-check text-xl"></i>
            </div>

            <h2 class="mt-4 text-lg font-semibold text-slate-900">
                Tidak Ada Pembayaran
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Saat ini tidak ada pembayaran yang menunggu verifikasi.
            </p>

        </div>

    <?php else: ?>

        <div class="mb-4 flex items-center gap-2 text-sm text-slate-600">

            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                <i class="fa-solid fa-clock"></i>
            </span>

            <span>
                <strong><?= count($payments) ?></strong>
                pembayaran menunggu verifikasi
            </span>

        </div>


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
                                Tagihan
                            </th>

                            <th class="px-5 py-4">
                                Pembayaran
                            </th>

                            <th class="px-5 py-4">
                                Tanggal
                            </th>

                            <th class="px-5 py-4 text-right">
                                Aksi
                            </th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        <?php foreach ($payments as $payment): ?>

                            <tr class="hover:bg-slate-50">

                                <td class="px-5 py-4">

                                    <div class="font-semibold text-slate-900">
                                        <?= e($payment['tenant_name']) ?>
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        <?= e($payment['tenant_email']) ?>
                                    </div>

                                </td>


                                <td class="px-5 py-4">

                                    <div class="font-semibold text-slate-900">
                                        <?= e($payment['room_number']) ?>
                                    </div>

                                    <div class="text-xs text-slate-500">
                                        <?= e($payment['room_type']) ?>
                                    </div>

                                </td>


                                <td class="px-5 py-4">

                                    <div class="font-medium text-slate-900">
                                        <?= e($payment['bill_number']) ?>
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        <?= rupiah($payment['bill_amount']) ?>
                                    </div>

                                </td>


                                <td class="px-5 py-4">

                                    <div class="font-semibold text-slate-900">
                                        <?= rupiah($payment['amount']) ?>
                                    </div>

                                    <span class="mt-1 inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700">
                                        Menunggu Verifikasi
                                    </span>

                                </td>


                                <td class="px-5 py-4 text-slate-600">

                                    <?= date(
                                        'd/m/Y H:i',
                                        strtotime($payment['payment_date'])
                                    ) ?>

                                </td>


                                <td class="px-5 py-4 text-right">

                                    <a
                                        href="/kost-management/admin/payments/detail.php?id=<?= (int) $payment['id'] ?>"
                                        class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white hover:bg-slate-700"
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

