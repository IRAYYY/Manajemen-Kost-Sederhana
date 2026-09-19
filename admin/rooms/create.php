
<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

$pageTitle = 'Tambah Kamar';

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">

    <a
        href="/kost-management/admin/rooms/index.php"
        class="mb-5 inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-900"
    >
        <i class="fa-solid fa-arrow-left"></i>
        Kembali ke Kamar
    </a>


    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">

        <div class="mb-6">

            <h1 class="text-2xl font-bold text-slate-900">
                Tambah Kamar
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Masukkan informasi kamar yang akan tersedia untuk penyewa.
            </p>

        </div>


        <?php if (isset($_SESSION['room_error'])): ?>

            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <?= e($_SESSION['room_error']) ?>
            </div>

            <?php unset($_SESSION['room_error']); ?>

        <?php endif; ?>


        <form
            action="/kost-management/admin/rooms/store.php"
            method="POST"
            class="space-y-5"
        >

            <!-- Nomor Kamar -->

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
                    value="<?= old('room_number') ?>"
                    required
                    maxlength="20"
                    placeholder="Contoh: A01"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-100"
                >

            </div>


            <!-- Tipe -->

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
                    value="<?= old('type') ?>"
                    required
                    maxlength="50"
                    placeholder="Contoh: Standard"
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
                        value="<?= old('monthly_price') ?>"
                        required
                        min="0"
                        step="1000"
                        placeholder="1000000"
                        class="w-full rounded-xl border border-slate-300 py-3 pl-12 pr-4 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-100"
                    >

                </div>

                <p class="mt-1 text-xs text-slate-500">
                    Masukkan nominal tanpa titik atau koma.
                </p>

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
                    placeholder="Contoh: WiFi, AC, kamar mandi dalam, meja, lemari"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-100"
                ><?= old('facilities') ?></textarea>

            </div>


            <!-- Button -->

            <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">

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
                    Simpan Kamar
                </button>

            </div>

        </form>

    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

