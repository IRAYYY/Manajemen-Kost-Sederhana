<?php

if (!isset($pageTitle)) {
    $pageTitle = 'Sistem Manajemen Kost';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= e($pageTitle) ?> - Sistem Manajemen Kost
    </title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    >

    <link
        rel="stylesheet"
        href="/kost-management/assets/css/app.css"
    >
</head>

<body class="bg-slate-50 text-slate-800">

<?php require __DIR__ . '/navbar.php'; ?>

<main>