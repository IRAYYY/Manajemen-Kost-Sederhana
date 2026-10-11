<?php

$pageTitle = 'Profil Saya & Verifikasi KTP';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireTenant();

$user = refreshCurrentUser($pdo);

$successMessage = $_SESSION['profile_success'] ?? null;
$errorMessage = $_SESSION['profile_error'] ?? null;

unset($_SESSION['profile_success']);
unset($_SESSION['profile_error']);

$verificationStatus = $user['verification_status'] ?? 'belum_verifikasi';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">

    <!-- Header -->
    <div class="mb-8">
        <a
            href="/kost-management/tenant/dashboard.php"
            class="mb-3 inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-900"
        >
            <i class="fa-solid fa-arrow-left"></i>
            Kembali ke Dashboard
        </a>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">
                    Profil Saya
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Lengkapi identitas dan upload KTP untuk verifikasi akun penyewa.
                </p>
            </div>

            <div>
                <span class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-xs font-semibold <?= verificationStatusClass($verificationStatus) ?>">
                    <i class="fa-solid <?= $verificationStatus === 'terverifikasi' ? 'fa-circle-check' : ($verificationStatus === 'menunggu_verifikasi' ? 'fa-clock' : 'fa-circle-exclamation') ?>"></i>
                    <?= e(verificationStatusLabel($verificationStatus)) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($successMessage): ?>
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-circle-check mt-0.5 text-emerald-600"></i>
                <div>
                    <p class="font-semibold">Berhasil</p>
                    <p class="mt-0.5"><?= e($successMessage) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-circle-exclamation mt-0.5 text-red-600"></i>
                <div>
                    <p class="font-semibold">Perhatian</p>
                    <p class="mt-0.5"><?= e($errorMessage) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Status Banner -->
    <?php if ($verificationStatus === 'belum_verifikasi'): ?>
        <div class="mb-8 rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                    <i class="fa-solid fa-id-card text-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-amber-900">
                        Profil Belum Diverifikasi
                    </h3>
                    <p class="mt-1 text-sm leading-6 text-amber-800">
                        Anda harus melengkapi data profil dan mengunggah foto KTP Anda (maksimal 1 gambar). Setelah data dikirimkan, admin akan memeriksa kesesuaian data Anda sebelum Anda dapat memesan kamar.
                    </p>
                    <?php if (!empty($user['verification_notes'])): ?>
                        <div class="mt-3 rounded-lg bg-amber-100/70 p-3 text-xs text-amber-900">
                            <strong>Catatan dari Admin:</strong> <?= e($user['verification_notes']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php elseif ($verificationStatus === 'menunggu_verifikasi'): ?>
        <div class="mb-8 rounded-2xl border border-blue-200 bg-blue-50 p-5">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                    <i class="fa-solid fa-clock text-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-blue-900">
                        Profil Sedang Menunggu Verifikasi Admin
                    </h3>
                    <p class="mt-1 text-sm leading-6 text-blue-800">
                        Data profil dan foto KTP Anda telah tersimpan dan sedang dalam antrean verifikasi oleh admin. Anda tetap dapat memperbarui nomor HP, alamat, atau mengganti foto KTP jika data sebelumnya kurang jelas.
                    </p>
                </div>
            </div>
        </div>
    <?php elseif ($verificationStatus === 'terverifikasi'): ?>
        <div class="mb-8 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                    <i class="fa-solid fa-shield-check text-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-emerald-900">
                        Profil Anda Telah Terverifikasi
                    </h3>
                    <p class="mt-1 text-sm leading-6 text-emerald-800">
                        Identitas KTP Anda telah diperiksa dan disetujui oleh admin. Akun Anda telah memenuhi syarat penuh untuk melakukan pemesanan kamar kost.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Profile Form -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form
            action="/kost-management/actions/update-profile.php"
            method="POST"
            enctype="multipart/form-data"
            class="p-6 sm:p-8 space-y-6"
        >
            <!-- Data Pribadi -->
            <div>
                <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-user text-slate-500"></i>
                    Informasi Data Diri
                </h2>

                <div class="grid gap-5 sm:grid-cols-2">
                    <!-- Nama Lengkap -->
                    <div>
                        <label for="name" class="mb-2 block text-sm font-medium text-slate-700">
                            Nama Lengkap (Sesuai KTP)
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?= e($user['name']) ?>"
                            required
                            maxlength="100"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-100"
                        >
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="mb-2 block text-sm font-medium text-slate-700">
                            Email Terdaftar
                        </label>
                        <input
                            type="email"
                            id="email"
                            value="<?= e($user['email']) ?>"
                            disabled
                            class="w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm text-slate-500"
                        >
                        <p class="mt-1 text-xs text-slate-400">Email tidak dapat diubah.</p>
                    </div>

                    <!-- Nomor HP -->
                    <div class="sm:col-span-2">
                        <label for="phone" class="mb-2 block text-sm font-medium text-slate-700">
                            Nomor WhatsApp / HP Aktif
                        </label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            value="<?= e($user['phone'] ?? '') ?>"
                            required
                            placeholder="Contoh: 08123456789"
                            maxlength="20"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-100"
                        >
                    </div>

                    <!-- Alamat Asal -->
                    <div class="sm:col-span-2">
                        <label for="address" class="mb-2 block text-sm font-medium text-slate-700">
                            Alamat Asal / Alamat Sesuai KTP
                        </label>
                        <textarea
                            id="address"
                            name="address"
                            rows="3"
                            required
                            placeholder="Masukkan alamat lengkap sesuai identitas KTP Anda..."
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-100"
                        ><?= e($user['address'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Upload KTP -->
            <div class="border-t border-slate-100 pt-6">
                <h2 class="text-lg font-bold text-slate-900 mb-2 flex items-center gap-2">
                    <i class="fa-solid fa-address-card text-slate-500"></i>
                    Upload Foto KTP (Maksimal 1 Gambar)
                </h2>
                <p class="text-sm text-slate-500 mb-5">
                    Unggah 1 foto KTP asli yang jelas, tidak buram, dan semua teks dapat terbaca. Foto ini digunakan admin untuk memverifikasi akun Anda.
                </p>

                <?php if (!empty($user['ktp_file'])): ?>
                    <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-4 max-w-md">
                        <p class="text-xs font-semibold text-slate-600 mb-2">Foto KTP yang Tersimpan Saat Ini:</p>
                        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                            <img
                                src="/kost-management/uploads/ktp/<?= e($user['ktp_file']) ?>"
                                alt="Foto KTP <?= e($user['name']) ?>"
                                class="h-52 w-full object-contain p-2"
                            >
                        </div>
                        <div class="mt-3 flex items-center justify-between">
                            <span class="text-xs text-slate-500">KTP Tersimpan</span>
                            <a
                                href="/kost-management/uploads/ktp/<?= e($user['ktp_file']) ?>"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-800 hover:text-blue-600"
                            >
                                <i class="fa-solid fa-up-right-from-square"></i>
                                Buka Foto Penuh
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <div>
                    <label for="ktp_file" class="mb-2 block text-sm font-medium text-slate-700">
                        <?= !empty($user['ktp_file']) ? 'Ganti Foto KTP (Pilih file baru jika ingin mengganti)' : 'Pilih Foto KTP' ?>
                    </label>

                    <input
                        type="file"
                        id="ktp_file"
                        name="ktp_file"
                        accept=".jpg,.jpeg,.png,.webp"
                        <?= empty($user['ktp_file']) ? 'required' : '' ?>
                        class="block w-full cursor-pointer rounded-xl border border-slate-300 bg-white text-sm text-slate-600 file:mr-4 file:border-0 file:bg-slate-100 file:px-4 file:py-3 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200"
                    >

                    <p class="mt-2 text-xs text-slate-500">
                        Format: JPG, JPEG, PNG, atau WEBP. Maksimal 1 file, ukuran maksimal 2 MB.
                    </p>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="border-t border-slate-100 pt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a
                    href="/kost-management/tenant/dashboard.php"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700"
                >
                    <i class="fa-solid fa-paper-plane"></i>
                    Simpan & Ajukan Verifikasi
                </button>
            </div>
        </form>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
