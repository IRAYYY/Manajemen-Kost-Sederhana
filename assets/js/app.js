document.addEventListener('DOMContentLoaded', () => {
    console.log('Kost Management App siap.');
});

function openNoteModal(title, message) {
    const modal = document.getElementById('noteModal');
    const titleEl = document.getElementById('noteModalTitle');
    const bodyEl = document.getElementById('noteModalBody');

    if (modal && titleEl && bodyEl) {
        titleEl.textContent = title || 'Catatan Pembayaran';
        bodyEl.textContent = message || 'Tidak ada catatan.';
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
}

function closeNoteModal() {
    const modal = document.getElementById('noteModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeNoteModal();
    }
});

document.addEventListener('click', (e) => {
    const modal = document.getElementById('noteModal');
    if (e.target === modal) {
        closeNoteModal();
    }
});