<?php

$pageTitle = 'Beranda';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="bg-slate-900">
    <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">

        <div class="max-w-3xl">

            <span class="mb-4 inline-block rounded-full bg-white/10 px-4 py-2 text-sm text-white">
                Sistem Manajemen Kost
            </span>

            <h1 class="text-4xl font-bold tracking-tight text-white sm:text-5xl">
                Temukan kamar kost yang sesuai dengan kebutuhan Anda.
            </h1>

            <p class="mt-6 text-lg leading-8 text-slate-300">
                Lihat kamar yang tersedia, pilih durasi sewa,
                lakukan pemesanan, dan kelola pembayaran dengan mudah.
            </p>

            <div class="mt-8 flex flex-wrap gap-4">

                <a
                    href="/kost-management/public/rooms.php"
                    class="rounded-lg bg-white px-5 py-3 font-semibold text-slate-900 hover:bg-slate-100"
                >
                    <i class="fa-solid fa-door-open mr-2"></i>
                    Lihat Kamar
                </a>

                <a
                    href="/kost-management/public/register.php"
                    class="rounded-lg border border-white/30 px-5 py-3 font-semibold text-white hover:bg-white/10"
                >
                    <i class="fa-solid fa-user-plus mr-2"></i>
                    Daftar
                </a>

            </div>

        </div>

    </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">

    <div class="mb-10">
        <p class="text-sm font-semibold uppercase tracking-wider text-slate-500">
            Fitur
        </p>

        <h2 class="mt-2 text-3xl font-bold text-slate-900">
            Kelola kost dengan lebih mudah
        </h2>
    </div>

    <div class="grid gap-6 md:grid-cols-3">

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100">
                <i class="fa-solid fa-door-open text-xl"></i>
            </div>

            <h3 class="text-lg font-semibold">
                Informasi Kamar
            </h3>

            <p class="mt-2 text-sm leading-6 text-slate-500">
                Lihat harga, fasilitas, dan status kamar
                secara mudah.
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100">
                <i class="fa-solid fa-calendar-check text-xl"></i>
            </div>

            <h3 class="text-lg font-semibold">
                Pemesanan
            </h3>

            <p class="mt-2 text-sm leading-6 text-slate-500">
                Pesan kamar dan tentukan jumlah bulan
                yang ingin disewa.
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100">
                <i class="fa-solid fa-receipt text-xl"></i>
            </div>

            <h3 class="text-lg font-semibold">
                Pembayaran
            </h3>

            <p class="mt-2 text-sm leading-6 text-slate-500">
                Upload bukti pembayaran dan pantau
                status verifikasi.
            </p>
        </div>

    </div>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>