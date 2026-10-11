
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
            enctype="multipart/form-data"
            class="space-y-5"
        >

            <!-- Nomor Kamar -->
            <div>
                <label for="room_number" class="mb-2 block text-sm font-medium text-slate-700">
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
                <label for="type" class="mb-2 block text-sm font-medium text-slate-700">
                    Tipe Kamar
                </label>
                <select
                    id="type"
                    name="type"
                    required
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-100"
                >
                    <option value="standard" <?= old('type') === 'standard' ? 'selected' : '' ?>>Standard</option>
                    <option value="deluxe" <?= old('type') === 'deluxe' ? 'selected' : '' ?>>Deluxe</option>
                </select>
            </div>


            <!-- Kapasitas -->
            <div>
                <label for="capacity" class="mb-2 block text-sm font-medium text-slate-700">
                    Jumlah Orang Tiap Kamar (Kapasitas)
                </label>
                <input
                    type="number"
                    id="capacity"
                    name="capacity"
                    value="<?= old('capacity', '1') ?>"
                    required
                    min="1"
                    max="10"
                    placeholder="Contoh: 1"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-100"
                >
            </div>


            <!-- Harga -->
            <div>
                <label for="monthly_price" class="mb-2 block text-sm font-medium text-slate-700">
                    Harga Per Bulan
                </label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-medium text-slate-400">Rp</span>
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
                <p class="mt-1 text-xs text-slate-500">Masukkan nominal tanpa titik atau koma.</p>
            </div>


            <!-- Fasilitas -->
            <div>
                <label for="facilities" class="mb-2 block text-sm font-medium text-slate-700">
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


            <!-- Foto Kamar (Multiple) -->
            <div>
                <label for="photos" class="mb-2 block text-sm font-medium text-slate-700">
                    Foto Kamar
                    <span class="ml-1 text-xs font-normal text-slate-400">(Bisa lebih dari 1 foto)</span>
                </label>

                <!-- Drop Zone -->
                <div
                    id="dropZone"
                    class="relative flex min-h-[120px] cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-6 text-center transition hover:border-slate-500 hover:bg-slate-100"
                    onclick="document.getElementById('photos').click()"
                >
                    <i class="fa-solid fa-images text-3xl text-slate-400"></i>
                    <div>
                        <p class="text-sm font-medium text-slate-700">Klik atau seret foto ke sini</p>
                        <p class="mt-0.5 text-xs text-slate-400">JPG, JPEG, PNG, WEBP — Maks. 2 MB per foto</p>
                    </div>
                </div>

                <input
                    type="file"
                    id="photos"
                    name="photos[]"
                    accept=".jpg,.jpeg,.png,.webp"
                    multiple
                    class="hidden"
                    onchange="previewPhotos(this)"
                >

                <!-- Preview Grid -->
                <div id="photoPreview" class="mt-3 hidden">
                    <p class="mb-2 text-xs font-semibold text-slate-600">Preview foto yang dipilih:</p>
                    <div id="previewGrid" class="grid grid-cols-3 gap-2 sm:grid-cols-4"></div>
                </div>

            </div>


            <!-- Buttons -->
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

<script>
function previewPhotos(input) {
    const grid   = document.getElementById('previewGrid');
    const wrap   = document.getElementById('photoPreview');
    const zone   = document.getElementById('dropZone');
    grid.innerHTML = '';

    if (!input.files || input.files.length === 0) {
        wrap.classList.add('hidden');
        return;
    }

    wrap.classList.remove('hidden');
    zone.innerHTML = `<i class="fa-solid fa-check-circle text-emerald-500 text-2xl"></i>
                      <p class="text-sm font-medium text-emerald-700">${input.files.length} foto dipilih</p>
                      <p class="text-xs text-slate-400 mt-1">Klik untuk mengganti pilihan</p>`;

    Array.from(input.files).forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const div = document.createElement('div');
            div.className = 'relative aspect-square overflow-hidden rounded-lg border border-slate-200 bg-slate-100';
            div.innerHTML = `<img src="${e.target.result}" class="h-full w-full object-cover">
                             <span class="absolute bottom-1 right-1 rounded bg-slate-900/70 px-1.5 py-0.5 text-xs text-white">${i + 1}</span>`;
            grid.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}

// Drag & Drop
const dropZone = document.getElementById('dropZone');
const photoInput = document.getElementById('photos');

dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('border-slate-500', 'bg-slate-100'); });
dropZone.addEventListener('dragleave', () => { dropZone.classList.remove('border-slate-500', 'bg-slate-100'); });
dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('border-slate-500', 'bg-slate-100');
    if (e.dataTransfer.files.length) {
        const dt = new DataTransfer();
        Array.from(e.dataTransfer.files).forEach(f => dt.items.add(f));
        photoInput.files = dt.files;
        previewPhotos(photoInput);
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
