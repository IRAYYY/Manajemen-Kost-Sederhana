</main>

<!-- Modal Catatan Pembayaran Pop-Up -->
<div
    id="noteModal"
    class="fixed inset-0 z-50 flex hidden items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm transition-opacity duration-200"
    role="dialog"
    aria-modal="true"
    aria-labelledby="noteModalTitle"
>
    <div
        id="noteModalContent"
        class="w-full max-w-md transform rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl transition-all sm:p-7"
    >
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                    <i class="fa-solid fa-comment-dots text-lg"></i>
                </div>
                <div>
                    <h3 id="noteModalTitle" class="text-lg font-bold text-slate-900">
                        Catatan Pembayaran
                    </h3>
                    <p class="text-xs text-slate-500">Detail catatan / alasan transaksi</p>
                </div>
            </div>

            <button
                type="button"
                onclick="closeNoteModal()"
                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none"
                aria-label="Tutup modal"
            >
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="mt-5">
            <div
                id="noteModalBody"
                class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm leading-relaxed text-slate-800 whitespace-pre-wrap"
            >
                -
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <button
                type="button"
                onclick="closeNoteModal()"
                class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700"
            >
                Tutup
            </button>
        </div>
    </div>
</div>

<footer class="mt-12 border-t border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-6 text-center text-sm text-slate-500 sm:px-6 lg:px-8">
        &copy; <?= date('Y') ?> Sistem Manajemen Kost. All rights reserved.
    </div>
</footer>

<script src="/kost-management/assets/js/app.js"></script>

</body>
</html>