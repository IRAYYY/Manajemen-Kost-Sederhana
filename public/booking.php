<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireTenant();

/*
|--------------------------------------------------------------------------
| Ambil ID kamar
|--------------------------------------------------------------------------
*/

$roomId = filter_input(INPUT_GET, 'room_id', FILTER_VALIDATE_INT);

if (!$roomId || $roomId <= 0) {
    redirect('/kost-management/public/rooms.php');
}

/*
|--------------------------------------------------------------------------
| Ambil data kamar
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        room_number,
        type,
        monthly_price,
        facilities,
        status
    FROM rooms
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$roomId]);

$room = $stmt->fetch();

if (!$room) {
    http_response_code(404);
    exit('Kamar tidak ditemukan.');
}

/*
|--------------------------------------------------------------------------
| Pastikan kamar tersedia
|--------------------------------------------------------------------------
*/

if ($room['status'] !== 'available') {
    $_SESSION['error'] = 'Kamar ini sudah tidak tersedia untuk dipesan.';

    redirect('/kost-management/public/rooms.php');
}

/*
|--------------------------------------------------------------------------
| Nama bulan
|--------------------------------------------------------------------------
*/

$monthNames = [
    1  => 'Januari',
    2  => 'Februari',
    3  => 'Maret',
    4  => 'April',
    5  => 'Mei',
    6  => 'Juni',
    7  => 'Juli',
    8  => 'Agustus',
    9  => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember',
];

/*
|--------------------------------------------------------------------------
| Tentukan bulan berjalan
|--------------------------------------------------------------------------
*/

$currentYear = (int) date('Y');
$currentMonth = (int) date('n');

/*
|--------------------------------------------------------------------------
| Bulan pertama yang boleh dipilih = bulan depan
|--------------------------------------------------------------------------
*/

$firstPeriod = new DateTime(
    sprintf(
        '%04d-%02d-01',
        $currentYear,
        $currentMonth
    )
);

$firstPeriod->modify('+1 month');

/*
|--------------------------------------------------------------------------
| Buat daftar periode
|--------------------------------------------------------------------------
|
| Kita sediakan 36 bulan ke depan.
|
| Contoh jika sekarang:
| September 2026
|
| Maka pilihan:
| Oktober 2026
| November 2026
| Desember 2026
| Januari 2027
| dst.
|
|--------------------------------------------------------------------------
*/

$periods = [];

$tempDate = clone $firstPeriod;

for ($i = 0; $i < 36; $i++) {

    $year = (int) $tempDate->format('Y');
    $month = (int) $tempDate->format('n');

    $periods[] = [
        'value' => $tempDate->format('Y-m'),
        'year' => $year,
        'month' => $month,
        'label' => $monthNames[$month] . ' ' . $year,
    ];

    $tempDate->modify('+1 month');
}

/*
|--------------------------------------------------------------------------
| Periode default
|--------------------------------------------------------------------------
*/

$defaultStart = $periods[0]['value'];
$defaultEnd = $periods[0]['value'];

/*
|--------------------------------------------------------------------------
| Harga kamar
|--------------------------------------------------------------------------
*/

$monthlyPrice = (float) $room['monthly_price'];

$pageTitle = 'Booking Kamar';

require_once __DIR__ . '/../includes/header.php';

?>

<section class="py-10 sm:py-14">

    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">

        <!-- Back -->
        <a
            href="/kost-management/public/room-detail.php?id=<?= (int) $room['id'] ?>"
            class="mb-6 inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900"
        >
            <i class="fa-solid fa-arrow-left"></i>
            Kembali ke detail kamar
        </a>


        <!-- Header -->
        <div class="mb-8">

            <div class="flex items-center gap-3">

                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-900 text-white">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>

                <div>

                    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">
                        Booking Kamar
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        Tentukan periode sewa kamar
                    </p>

                </div>

            </div>

        </div>


        <!-- Error -->
        <?php if (!empty($_SESSION['error'])): ?>

            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">

                <div class="flex items-start gap-3 text-sm text-red-700">

                    <i class="fa-solid fa-circle-exclamation mt-0.5"></i>

                    <span>
                        <?= e($_SESSION['error']) ?>
                    </span>

                </div>

            </div>

            <?php unset($_SESSION['error']); ?>

        <?php endif; ?>


        <!-- Room Information -->
        <div class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="flex items-center justify-between gap-4 p-5 sm:p-6">

                <div>

                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Kamar
                    </p>

                    <h2 class="mt-1 text-xl font-bold text-slate-900">
                        <?= e($room['room_number']) ?>
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        <?= e($room['type']) ?>
                    </p>

                </div>

                <div class="text-right">

                    <p class="text-xs text-slate-500">
                        Harga / bulan
                    </p>

                    <p class="mt-1 text-lg font-bold text-slate-900">
                        <?= rupiah($monthlyPrice) ?>
                    </p>

                </div>

            </div>

            <?php if (!empty($room['facilities'])): ?>

                <div class="border-t border-slate-100 px-5 py-4 sm:px-6">

                    <div class="flex items-start gap-3 text-sm text-slate-600">

                        <i class="fa-solid fa-list-check mt-0.5 text-slate-400"></i>

                        <div>

                            <span class="font-medium text-slate-700">
                                Fasilitas:
                            </span>

                            <?= e($room['facilities']) ?>

                        </div>

                    </div>

                </div>

            <?php endif; ?>

        </div>


        <!-- Information -->
        <div class="mb-6 rounded-2xl border border-blue-200 bg-blue-50 p-5">

            <div class="flex items-start gap-3">

                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                    <i class="fa-solid fa-circle-info"></i>
                </div>

                <div>

                    <h3 class="font-semibold text-blue-900">
                        Aturan periode sewa
                    </h3>

                    <ul class="mt-2 space-y-1.5 text-sm leading-6 text-blue-800">

                        <li>
                            <i class="fa-solid fa-check mr-1"></i>
                            Periode sewa selalu dimulai tanggal 1.
                        </li>

                        <li>
                            <i class="fa-solid fa-check mr-1"></i>
                            Bulan berjalan tidak dapat dipilih.
                        </li>

                        <li>
                            <i class="fa-solid fa-check mr-1"></i>
                            Periode paling awal adalah bulan depan.
                        </li>

                        <li>
                            <i class="fa-solid fa-check mr-1"></i>
                            Pembayaran dihitung berdasarkan jumlah bulan sewa.
                        </li>

                    </ul>

                </div>

            </div>

        </div>


        <!-- Form -->
        <form
            action="/kost-management/actions/booking.php"
            method="POST"
            id="bookingForm"
            class="space-y-6"
        >

            <input
                type="hidden"
                name="room_id"
                value="<?= (int) $room['id'] ?>"
            >


            <!-- Period -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">

                <div class="mb-5">

                    <h2 class="font-semibold text-slate-900">
                        Periode Sewa
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Pilih bulan mulai dan bulan terakhir sewa.
                    </p>

                </div>


                <div class="grid gap-5 sm:grid-cols-2">

                    <!-- Start -->
                    <div>

                        <label
                            for="start_period"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Mulai Sewa
                        </label>

                        <select
                            name="start_period"
                            id="start_period"
                            required
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-900 focus:ring-2 focus:ring-slate-200"
                        >

                            <?php foreach ($periods as $period): ?>

                                <option
                                    value="<?= e($period['value']) ?>"
                                    <?= $period['value'] === $defaultStart ? 'selected' : '' ?>
                                >
                                    <?= e($period['label']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                        <p class="mt-2 text-xs text-slate-500">
                            Selalu dimulai tanggal 1.
                        </p>

                    </div>


                    <!-- End -->
                    <div>

                        <label
                            for="end_period"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Sampai
                        </label>

                        <select
                            name="end_period"
                            id="end_period"
                            required
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-900 focus:ring-2 focus:ring-slate-200"
                        >

                            <?php foreach ($periods as $period): ?>

                                <option
                                    value="<?= e($period['value']) ?>"
                                    <?= $period['value'] === $defaultEnd ? 'selected' : '' ?>
                                >
                                    <?= e($period['label']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                        <p class="mt-2 text-xs text-slate-500">
                            Sampai hari terakhir bulan tersebut.
                        </p>

                    </div>

                </div>

            </div>


            <!-- Summary -->
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 bg-slate-50 px-5 py-4 sm:px-6">

                    <div class="flex items-center gap-2">

                        <i class="fa-solid fa-calculator text-slate-500"></i>

                        <h2 class="font-semibold text-slate-900">
                            Ringkasan Booking
                        </h2>

                    </div>

                </div>


                <div class="space-y-4 p-5 sm:p-6">

                    <!-- Periode -->
                    <div class="flex items-start justify-between gap-5">

                        <span class="text-sm text-slate-500">
                            Periode
                        </span>

                        <span
                            id="periodText"
                            class="text-right text-sm font-semibold text-slate-900"
                        >
                            -
                        </span>

                    </div>


                    <!-- Start date -->
                    <div class="flex items-start justify-between gap-5">

                        <span class="text-sm text-slate-500">
                            Mulai
                        </span>

                        <span
                            id="startDateText"
                            class="text-right text-sm font-medium text-slate-800"
                        >
                            -
                        </span>

                    </div>


                    <!-- End date -->
                    <div class="flex items-start justify-between gap-5">

                        <span class="text-sm text-slate-500">
                            Selesai
                        </span>

                        <span
                            id="endDateText"
                            class="text-right text-sm font-medium text-slate-800"
                        >
                            -
                        </span>

                    </div>


                    <!-- Divider -->
                    <div class="border-t border-slate-200 pt-4">

                        <div class="flex items-end justify-between gap-5">

                            <div>

                                <p class="text-sm text-slate-500">
                                    Durasi
                                </p>

                                <p
                                    id="durationText"
                                    class="mt-1 text-xl font-bold text-slate-900"
                                >
                                    1 bulan
                                </p>

                            </div>


                            <div class="text-right">

                                <p class="text-sm text-slate-500">
                                    Total
                                </p>

                                <p
                                    id="totalText"
                                    class="mt-1 text-2xl font-bold text-slate-900"
                                >
                                    <?= rupiah($monthlyPrice) ?>
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Expiration -->
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">

                <div class="flex items-start gap-3">

                    <i class="fa-solid fa-clock mt-0.5 text-amber-600"></i>

                    <div class="text-sm text-amber-800">

                        <p class="font-semibold">
                            Perhatikan batas booking
                        </p>

                        <p class="mt-1 leading-6">
                            Setelah booking dibuat, segera lakukan pembayaran
                            dan upload bukti pembayaran sesuai batas waktu
                            yang ditentukan sistem.
                        </p>

                    </div>

                </div>

            </div>


            <!-- Submit -->
            <button
                type="submit"
                id="submitButton"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-3.5 font-semibold text-white shadow-sm transition hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
            >
                <i class="fa-solid fa-calendar-check"></i>
                Lanjutkan Booking
            </button>

        </form>

    </div>

</section>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const startPeriod =
        document.getElementById('start_period');

    const endPeriod =
        document.getElementById('end_period');

    const periodText =
        document.getElementById('periodText');

    const startDateText =
        document.getElementById('startDateText');

    const endDateText =
        document.getElementById('endDateText');

    const durationText =
        document.getElementById('durationText');

    const totalText =
        document.getElementById('totalText');

    const bookingForm =
        document.getElementById('bookingForm');


    const monthlyPrice =
        <?= json_encode($monthlyPrice) ?>;


    const monthNames = [
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];


    /*
    |--------------------------------------------------------------------------
    | Parse periode
    |--------------------------------------------------------------------------
    |
    | Format:
    | YYYY-MM
    |
    */

    function parsePeriod(value) {

        const parts = value.split('-');

        return {
            year: parseInt(parts[0], 10),
            month: parseInt(parts[1], 10)
        };

    }


    /*
    |--------------------------------------------------------------------------
    | Hitung index bulan
    |--------------------------------------------------------------------------
    */

    function monthIndex(year, month) {

        return (year * 12) + month;

    }


    /*
    |--------------------------------------------------------------------------
    | Format tanggal
    |--------------------------------------------------------------------------
    */

    function formatDate(date) {

        return new Intl.DateTimeFormat('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        }).format(date);

    }


    /*
    |--------------------------------------------------------------------------
    | Format Rupiah
    |--------------------------------------------------------------------------
    */

    function formatRupiah(value) {

        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        }).format(value);

    }


    /*
    |--------------------------------------------------------------------------
    | Perbarui perhitungan
    |--------------------------------------------------------------------------
    */

    function updateCalculation() {

        const start =
            parsePeriod(startPeriod.value);

        const end =
            parsePeriod(endPeriod.value);


        const startIndex =
            monthIndex(
                start.year,
                start.month
            );

        const endIndex =
            monthIndex(
                end.year,
                end.month
            );


        /*
        |--------------------------------------------------------------------------
        | Validasi periode
        |--------------------------------------------------------------------------
        */

        if (endIndex < startIndex) {

            periodText.textContent =
                'Periode tidak valid';

            startDateText.textContent =
                '-';

            endDateText.textContent =
                '-';

            durationText.textContent =
                '-';

            totalText.textContent =
                'Rp 0';

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | Durasi
        |--------------------------------------------------------------------------
        |
        | Okt -> Okt = 1
        | Okt -> Nov = 2
        | Okt -> Des = 3
        |
        */

        const duration =
            endIndex - startIndex + 1;


        /*
        |--------------------------------------------------------------------------
        | Tanggal mulai
        |--------------------------------------------------------------------------
        */

        const startDate =
            new Date(
                start.year,
                start.month - 1,
                1
            );


        /*
        |--------------------------------------------------------------------------
        | Tanggal selesai
        |--------------------------------------------------------------------------
        |
        | Hari ke-0 dari bulan berikutnya
        | menghasilkan hari terakhir bulan tersebut.
        |
        */

        const endDate =
            new Date(
                end.year,
                end.month,
                0
            );


        /*
        |--------------------------------------------------------------------------
        | Total
        |--------------------------------------------------------------------------
        */

        const total =
            monthlyPrice * duration;


        /*
        |--------------------------------------------------------------------------
        | Update tampilan
        |--------------------------------------------------------------------------
        */

        periodText.textContent =
            `${monthNames[start.month - 1]} ${start.year} – ${monthNames[end.month - 1]} ${end.year}`;

        startDateText.textContent =
            formatDate(startDate);

        endDateText.textContent =
            formatDate(endDate);

        durationText.textContent =
            `${duration} bulan`;

        totalText.textContent =
            formatRupiah(total);

    }


    /*
    |--------------------------------------------------------------------------
    | Ketika periode mulai berubah
    |--------------------------------------------------------------------------
    */

    startPeriod.addEventListener('change', function () {

        const start =
            parsePeriod(startPeriod.value);

        const startIndex =
            monthIndex(
                start.year,
                start.month
            );


        /*
        |--------------------------------------------------------------------------
        | Jangan biarkan periode akhir lebih kecil
        |--------------------------------------------------------------------------
        */

        Array.from(endPeriod.options).forEach(function (option) {

            const optionPeriod =
                parsePeriod(option.value);

            const optionIndex =
                monthIndex(
                    optionPeriod.year,
                    optionPeriod.month
                );

            option.disabled =
                optionIndex < startIndex;

        });


        /*
        |--------------------------------------------------------------------------
        | Jika pilihan akhir sekarang invalid,
        | otomatis gunakan periode mulai.
        |--------------------------------------------------------------------------
        */

        const currentEnd =
            parsePeriod(endPeriod.value);

        const currentEndIndex =
            monthIndex(
                currentEnd.year,
                currentEnd.month
            );

        if (currentEndIndex < startIndex) {

            endPeriod.value =
                startPeriod.value;

        }

        updateCalculation();

    });


    /*
    |--------------------------------------------------------------------------
    | Ketika periode akhir berubah
    |--------------------------------------------------------------------------
    */

    endPeriod.addEventListener(
        'change',
        updateCalculation
    );


    /*
    |--------------------------------------------------------------------------
    | Validasi sebelum submit
    |--------------------------------------------------------------------------
    */

    bookingForm.addEventListener(
        'submit',
        function (event) {

            const start =
                parsePeriod(startPeriod.value);

            const end =
                parsePeriod(endPeriod.value);


            const startIndex =
                monthIndex(
                    start.year,
                    start.month
                );

            const endIndex =
                monthIndex(
                    end.year,
                    end.month
                );


            /*
            |--------------------------------------------------------------------------
            | Pastikan akhir >= mulai
            |--------------------------------------------------------------------------
            */

            if (endIndex < startIndex) {

                event.preventDefault();

                alert(
                    'Periode selesai tidak boleh lebih awal dari periode mulai.'
                );

                return;

            }


            /*
            |--------------------------------------------------------------------------
            | Pastikan mulai bukan bulan berjalan
            |--------------------------------------------------------------------------
            */

            const today =
                new Date();

            const currentIndex =
                monthIndex(
                    today.getFullYear(),
                    today.getMonth() + 1
                );


            if (startIndex <= currentIndex) {

                event.preventDefault();

                alert(
                    'Periode sewa harus dimulai bulan depan atau setelahnya.'
                );

                return;

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Initial state
    |--------------------------------------------------------------------------
    */

    startPeriod.dispatchEvent(
        new Event('change')
    );

    updateCalculation();

});

</script>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>