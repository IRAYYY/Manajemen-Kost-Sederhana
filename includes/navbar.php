
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
            <span>Kost Management</span>
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

                    <a
                        href="/kost-management/admin/rooms/index.php"
                        class="text-slate-600 transition hover:text-slate-900"
                    >
                        Kamar
                    </a>

                    <a
                        href="/kost-management/admin/tenants/index.php"
                        class="text-slate-600 transition hover:text-slate-900"
                    >
                        Tenant
                    </a>

                    <a
                        href="/kost-management/admin/payments/index.php"
                        class="text-slate-600 transition hover:text-slate-900"
                    >
                        Pembayaran
                    </a>

                <?php else: ?>

                    <a
                        href="/kost-management/tenant/dashboard.php"
                        class="text-slate-600 transition hover:text-slate-900"
                    >
                        Dashboard
                    </a>

                <?php endif; ?>


                <a
                    href="/kost-management/actions/logout.php"
                    class="text-red-600 transition hover:text-red-700"
                >
                    Keluar
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

