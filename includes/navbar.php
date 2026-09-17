<?php

$user = $_SESSION['user'] ?? null;

?>

<nav class="border-b border-slate-200 bg-white">

    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">

        <a
            href="/kost-management/public/index.php"
            class="flex items-center gap-2 font-bold text-slate-900"
        >
            <i class="fa-solid fa-house"></i>

            <span>
                Kost Management
            </span>
        </a>

        <div class="flex items-center gap-4 text-sm">

            <a
                href="/kost-management/public/rooms.php"
                class="text-slate-600 transition hover:text-slate-900"
            >
                Kamar
            </a>

            <?php if ($user): ?>

                <?php if ($user['role'] === 'admin'): ?>

                    <a
                        href="/kost-management/admin/dashboard.php"
                        class="text-slate-600 transition hover:text-slate-900"
                    >
                        Dashboard
                    </a>

                <?php else: ?>

                    <a
                        href="/kost-management/tenant/dashboard.php"
                        class="text-slate-600 transition hover:text-slate-900"
                    >
                        Dashboard
                    </a>

                <?php endif; ?>

                <span class="hidden text-slate-400 sm:inline">
                    |
                </span>

                <span class="hidden font-medium text-slate-700 sm:inline">
                    <?= e($user['name']) ?>
                </span>

                <a
                    href="/kost-management/actions/logout.php"
                    class="flex items-center gap-1 text-red-600 transition hover:text-red-700"
                >
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span class="hidden sm:inline">
                        Keluar
                    </span>
                </a>

            <?php else: ?>

                <a
                    href="/kost-management/public/login.php"
                    class="text-slate-600 transition hover:text-slate-900"
                >
                    Login
                </a>

                <a
                    href="/kost-management/public/register.php"
                    class="rounded-lg bg-slate-900 px-4 py-2 font-medium text-white transition hover:bg-slate-700"
                >
                    Daftar
                </a>

            <?php endif; ?>

        </div>

    </div>

</nav>