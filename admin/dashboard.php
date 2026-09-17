<?php

$pageTitle = 'Dashboard Admin';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$user = currentUser();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

    <div class="mb-8">

        <p class="text-sm font-medium text-slate-500">
            Dashboard Admin
        </p>

        <h1 class="mt-1 text-3xl font-bold text-slate-900">
            Halo, <?= e($user['name']) ?> 👋
        </h1>

        <p class="mt-2 text-slate-500">
            Kelola kamar, penyewa, tagihan, pembayaran, dan laporan.
        </p>

    </div>

    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100">
                <i class="fa-solid fa-door-open text-xl"></i>
            </div>

            <p class="mt-4 text-sm text-slate-500">
                Total Kamar
            </p>

            <p class="mt-1 text-3xl font-bold">
                -
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100">
                <i class="fa-solid fa-users text-xl"></i>
            </div>

            <p class="mt-4 text-sm text-slate-500">
                Penyewa Aktif
            </p>

            <p class="mt-1 text-3xl font-bold">
                -
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100">
                <i class="fa-solid fa-hourglass-half text-xl"></i>
            </div>

            <p class="mt-4 text-sm text-slate-500">
                Menunggu Verifikasi
            </p>

            <p class="mt-1 text-3xl font-bold">
                -
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100">
                <i class="fa-solid fa-money-bill-wave text-xl"></i>
            </div>

            <p class="mt-4 text-sm text-slate-500">
                Pendapatan
            </p>

            <p class="mt-1 text-3xl font-bold">
                -
            </p>
        </div>

    </div>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>