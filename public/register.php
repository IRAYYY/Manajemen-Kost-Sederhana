<?php

$pageTitle = 'Daftar';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    redirectByRole();
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="mx-auto max-w-lg px-4 py-12 sm:px-6">

    <div class="mb-8 text-center">

        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-900 text-white">
            <i class="fa-solid fa-user-plus text-xl"></i>
        </div>

        <h1 class="text-3xl font-bold text-slate-900">
            Buat Akun
        </h1>

        <p class="mt-2 text-sm text-slate-500">
            Daftar untuk melakukan pemesanan kamar kost.
        </p>

    </div>

    <?php if (isset($_GET['error'])): ?>

        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <?= e($_GET['error']) ?>
        </div>

    <?php endif; ?>

    <form
        action="/kost-management/actions/register.php"
        method="POST"
        class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
    >

        <div>
            <label
                for="name"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Nama Lengkap
            </label>

            <input
                type="text"
                id="name"
                name="name"
                required
                value="<?= old('name') ?>"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-900 focus:ring-2 focus:ring-slate-200"
                placeholder="Masukkan nama lengkap"
            >
        </div>

        <div>
            <label
                for="email"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Email
            </label>

            <input
                type="email"
                id="email"
                name="email"
                required
                value="<?= old('email') ?>"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-900 focus:ring-2 focus:ring-slate-200"
                placeholder="contoh@email.com"
            >
        </div>

        <div>
            <label
                for="phone"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Nomor HP
            </label>

            <input
                type="tel"
                id="phone"
                name="phone"
                value="<?= old('phone') ?>"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-900 focus:ring-2 focus:ring-slate-200"
                placeholder="08xxxxxxxxxx"
            >
        </div>

        <div>
            <label
                for="password"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Password
            </label>

            <input
                type="password"
                id="password"
                name="password"
                required
                minlength="8"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-900 focus:ring-2 focus:ring-slate-200"
                placeholder="Minimal 8 karakter"
            >
        </div>

        <div>
            <label
                for="password_confirmation"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Konfirmasi Password
            </label>

            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                required
                minlength="8"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-slate-900 focus:ring-2 focus:ring-slate-200"
                placeholder="Ulangi password"
            >
        </div>

        <button
            type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-700"
        >
            <i class="fa-solid fa-user-plus"></i>
            Daftar
        </button>

    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        Sudah memiliki akun?

        <a
            href="/kost-management/public/login.php"
            class="font-semibold text-slate-900 hover:underline"
        >
            Login
        </a>
    </p>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>