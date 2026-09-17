<?php

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function redirect($url)
{
    header("Location: {$url}");
    exit;
}

function isPost()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function old($key, $default = '')
{
    return e($_POST[$key] ?? $default);
}

function rupiah($amount)
{
    return 'Rp ' . number_format(
        (float) $amount,
        0,
        ',',
        '.'
    );
}

function roomStatusLabel($status)
{
    return match ($status) {
        'available' => 'Tersedia',
        'booked' => 'Dibooking',
        'occupied' => 'Terisi',
        default => 'Tidak diketahui',
    };
}

function roomStatusClass($status)
{
    return match ($status) {
        'available' => 'bg-green-100 text-green-700',
        'booked' => 'bg-yellow-100 text-yellow-700',
        'occupied' => 'bg-red-100 text-red-700',
        default => 'bg-slate-100 text-slate-700',
    };
}

function formatDateIndonesia($date)
{
    if (!$date) {
        return '-';
    }

    return date('d/m/Y', strtotime($date));
}