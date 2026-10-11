<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

$pageTitle = 'Verifikasi Profil User';


/*
|--------------------------------------------------------------------------
| Filter Tab
|--------------------------------------------------------------------------
*/

$tab = $_GET['tab'] ?? 'menunggu_verifikasi';
$allowedTabs = ['menunggu_verifikasi', 'belum_verifikasi', 'terverifikasi'];
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'menunggu_verifikasi';
}

/*
|--------------------------------------------------------------------------
| Hitung badge per tab
|--------------------------------------------------------------------------
*/

$countStmt = $pdo->query(
    "SELECT verification_status, COUNT(*) as total
     FROM users
     WHERE role = 'tenant'
     GROUP BY verification_status"
);
$countMap = [];
foreach ($countStmt->fetchAll() as $row) {
    $countMap[$row['verification_status']] = (int) $row['total'];
}

/*
|--------------------------------------------------------------------------
| Ambil daftar user
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        id,
        name,
        email,
        phone,
        address,
        ktp_file,
        verification_status,
        verification_notes,
        verified_at,
        created_at

     FROM users

     WHERE role = 'tenant'
       AND verification_status = ?

     ORDER BY created_at DESC"
);

$stmt->execute([$tab]);
$users = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    <!-- Header -->
    <div class="mb-8">

        <a
            href="/kost-management/admin/dashboard.php"
            class="mb-3 inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-900"
        >
            <i class="fa-solid fa-arrow-left"></i>
            Dashboard
        </a>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Verifikasi Profil User
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Periksa foto KTP dan data identitas calon penyewa sebelum disetujui.
                </p>
            </div>

        </div>

    </div>


    <?php if (isset($_SESSION['user_verify_success'])): ?>

        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i>
            <?= e($_SESSION['user_verify_success']) ?>
        </div>

        <?php unset($_SESSION['user_verify_success']); ?>

    <?php endif; ?>

    <?php if (isset($_SESSION['user_verify_error'])): ?>

        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= e($_SESSION['user_verify_error']) ?>
        </div>

        <?php unset($_SESSION['user_verify_error']); ?>

    <?php endif; ?>


    <!-- Tabs -->
    <div class="mb-6 flex flex-wrap gap-2 border-b border-slate-200 pb-0">

        <?php
        $tabs = [
            'menunggu_verifikasi' => ['label' => 'Menunggu Verifikasi', 'class' => 'amber'],
            'belum_verifikasi'    => ['label' => 'Belum Verifikasi', 'class' => 'slate'],
            'terverifikasi'       => ['label' => 'Terverifikasi', 'class' => 'emerald'],
        ];
        ?>

        <?php foreach ($tabs as $tabKey => $tabInfo): ?>
            <?php $count = $countMap[$tabKey] ?? 0; ?>
            <?php $isActive = $tab === $tabKey; ?>
            <a
                href="?tab=<?= $tabKey ?>"
                class="inline-flex items-center gap-2 rounded-t-xl border-b-2 px-4 py-3 text-sm font-semibold transition
                    <?= $isActive
                        ? 'border-slate-900 text-slate-900 bg-white'
                        : 'border-transparent text-slate-500 hover:text-slate-900 hover:border-slate-300'
                    ?>"
            >
                <?= $tabInfo['label'] ?>
                <?php if ($count > 0): ?>
                    <span class="rounded-full <?= $isActive ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600' ?> px-2 py-0.5 text-xs font-semibold">
                        <?= $count ?>
                    </span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>

    </div>


    <!-- User Cards -->

    <?php if (!$users): ?>

        <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">

            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                <i class="fa-solid fa-id-card text-lg"></i>
            </div>

            <p class="mt-3 font-semibold text-slate-900">
                Tidak ada user dengan status ini.
            </p>

        </div>

    <?php else: ?>

        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">

            <?php foreach ($users as $u): ?>

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm flex flex-col">

                    <!-- KTP Preview -->
                    <div class="h-44 w-full overflow-hidden rounded-t-2xl bg-slate-100">
                        <?php if (!empty($u['ktp_file'])): ?>
                            <img
                                src="/kost-management/uploads/ktp/<?= e($u['ktp_file']) ?>"
                                alt="KTP <?= e($u['name']) ?>"
                                class="h-full w-full object-cover cursor-pointer transition hover:scale-105"
                                onclick="openNoteModal('Foto KTP <?= e($u['name']) ?>', '')"
                                title="Klik untuk membuka di tab baru"
                                onclick="window.open('/kost-management/uploads/ktp/<?= e($u['ktp_file']) ?>', '_blank')"
                            >
                        <?php else: ?>
                            <div class="flex h-full w-full flex-col items-center justify-center text-slate-400">
                                <i class="fa-solid fa-id-card text-4xl"></i>
                                <span class="mt-2 text-xs font-medium">KTP belum diunggah</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Info -->
                    <div class="flex flex-1 flex-col p-5">

                        <div class="flex items-start justify-between gap-3">

                            <div>
                                <h3 class="font-bold text-slate-900"><?= e($u['name']) ?></h3>
                                <p class="mt-0.5 text-xs text-slate-500 break-all"><?= e($u['email']) ?></p>
                            </div>

                            <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold <?= verificationStatusClass($u['verification_status']) ?>">
                                <?= e(verificationStatusLabel($u['verification_status'])) ?>
                            </span>

                        </div>

                        <div class="mt-4 space-y-2 text-xs text-slate-600">

                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-phone w-4 text-slate-400"></i>
                                <?= e($u['phone'] ?: 'Belum diisi') ?>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-envelope w-4 text-slate-400"></i>
                                <?= e($u['email'] ?: 'Belum diisi') ?>
                            </div>

                            <div class="flex items-start gap-2">
                                <i class="fa-solid fa-location-dot mt-0.5 w-4 text-slate-400"></i>
                                <span class="leading-5"><?= e($u['address'] ?: 'Belum diisi') ?></span>
                            </div>

                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-calendar w-4 text-slate-400"></i>
                                Daftar: <?= date('d/m/Y', strtotime($u['created_at'])) ?>
                            </div>

                            <?php if (!empty($u['ktp_file'])): ?>
                                <div class="pt-1">
                                    <a
                                        href="/kost-management/uploads/ktp/<?= e($u['ktp_file']) ?>"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 font-semibold text-blue-600 hover:text-blue-700"
                                    >
                                        <i class="fa-solid fa-up-right-from-square"></i>
                                        Lihat Foto KTP
                                    </a>
                                </div>
                            <?php endif; ?>

                        </div>


                        <?php if (!empty($u['verification_notes'])): ?>
                            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                                <strong>Catatan Admin Sebelumnya:</strong>
                                <?= e($u['verification_notes']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($u['verified_at']): ?>
                            <div class="mt-3 text-xs text-emerald-600">
                                <i class="fa-solid fa-check"></i>
                                Diverifikasi: <?= date('d/m/Y H:i', strtotime($u['verified_at'])) ?>
                            </div>
                        <?php endif; ?>


                        <!-- Actions -->
                        <?php if ($u['verification_status'] !== 'terverifikasi'): ?>

                            <form
                                action="/kost-management/actions/verify-user.php"
                                method="POST"
                                class="mt-5 space-y-3 border-t border-slate-100 pt-4"
                            >

                                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                <input type="hidden" name="tab" value="<?= e($tab) ?>">

                                <div>
                                    <label for="notes_<?= $u['id'] ?>" class="mb-1.5 block text-xs font-semibold text-slate-600">
                                        Catatan (Opsional)
                                    </label>
                                    <textarea
                                        id="notes_<?= $u['id'] ?>"
                                        name="notes"
                                        rows="2"
                                        placeholder="Catatan untuk user (alasan penolakan, dll.)"
                                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-100"
                                    ></textarea>
                                </div>

                                <div class="flex gap-2">

                                    <?php if (!empty($u['ktp_file'])): ?>
                                        <button
                                            type="submit"
                                            name="action"
                                            value="verify"
                                            onclick="return confirm('Verifikasi akun <?= e(addslashes($u['name'])) ?>?')"
                                            class="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-2.5 text-xs font-semibold text-white hover:bg-emerald-700"
                                        >
                                            <i class="fa-solid fa-check"></i>
                                            Verifikasi
                                        </button>
                                    <?php endif; ?>

                                    <button
                                        type="submit"
                                        name="action"
                                        value="reject"
                                        onclick="return confirm('Tolak / reset verifikasi akun <?= e(addslashes($u['name'])) ?>?')"
                                        class="flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-red-200 bg-red-50 px-3 py-2.5 text-xs font-semibold text-red-700 hover:bg-red-100"
                                    >
                                        <i class="fa-solid fa-xmark"></i>
                                        Tolak
                                    </button>

                                </div>

                            </form>

                        <?php else: ?>

                            <!-- Already verified — allow reverting -->
                            <form
                                action="/kost-management/actions/verify-user.php"
                                method="POST"
                                class="mt-5 border-t border-slate-100 pt-4"
                            >
                                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                <input type="hidden" name="tab" value="<?= e($tab) ?>">
                                <input type="hidden" name="action" value="reject">

                                <div class="mb-3">
                                    <label for="notes_rev_<?= $u['id'] ?>" class="mb-1.5 block text-xs font-semibold text-slate-600">
                                        Catatan pencabutan (Opsional)
                                    </label>
                                    <textarea
                                        id="notes_rev_<?= $u['id'] ?>"
                                        name="notes"
                                        rows="2"
                                        placeholder="Alasan pencabutan verifikasi"
                                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-100"
                                    ></textarea>
                                </div>

                                <button
                                    type="submit"
                                    onclick="return confirm('Cabut verifikasi akun ini?')"
                                    class="flex w-full items-center justify-center gap-1.5 rounded-xl border border-red-200 bg-red-50 px-3 py-2.5 text-xs font-semibold text-red-700 hover:bg-red-100"
                                >
                                    <i class="fa-solid fa-shield-halved"></i>
                                    Cabut Verifikasi
                                </button>

                            </form>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
