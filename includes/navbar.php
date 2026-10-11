<?php
$user = $_SESSION['user'] ?? null;
// Mendapatkan path file saat ini untuk menentukan menu aktif
$current_page = $_SERVER['PHP_SELF'];
?>

<nav class="sticky top-0 z-50 border-b border-slate-200 bg-white/80 backdrop-blur-md shadow-xs">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3.5 sm:px-6 lg:px-8">
        
        <!-- Logo / Brand -->
        <a href="/kost-management/public/index.php" class="group flex items-center gap-2.5 font-bold text-slate-900 transition hover:opacity-90">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm shadow-indigo-200 transition group-hover:scale-105">
                <i class="fa-solid fa-house text-sm"></i>
            </div>
            <span class="text-base tracking-tight font-extrabold bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent">Kost Management</span>
        </a>

        <!-- Desktop Menu -->
        <div class="hidden md:flex items-center gap-1.5 text-sm font-medium">

            <!-- Menu Publik -->
            <?php if (!$user || $user['role'] !== 'admin'): ?>
                <a href="/kost-management/public/rooms.php" 
                   class="flex items-center gap-2 rounded-lg px-3.5 py-2 transition <?= str_contains($current_page, 'rooms.php') && !str_contains($current_page, 'admin') ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>">
                    <i class="fa-solid fa-door-open w-4 text-center"></i> Kamar
                </a>
            <?php endif; ?>

            <?php if ($user): ?>
                <?php if ($user['role'] === 'admin'): ?>
                    
                    <a href="/kost-management/admin/dashboard.php" 
                       class="flex items-center gap-2 rounded-lg px-3.5 py-2 transition <?= str_contains($current_page, 'admin/dashboard.php') ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>">
                        <i class="fa-solid fa-chart-pie w-4 text-center"></i> Dashboard
                    </a>

                    <a href="/kost-management/admin/rooms/index.php" 
                       class="flex items-center gap-2 rounded-lg px-3.5 py-2 transition <?= str_contains($current_page, 'admin/rooms/') ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>">
                        <i class="fa-solid fa-hotel w-4 text-center"></i> Kamar
                    </a>

                    <a href="/kost-management/admin/tenants/index.php" 
                       class="flex items-center gap-2 rounded-lg px-3.5 py-2 transition <?= str_contains($current_page, 'admin/tenants/') ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>">
                        <i class="fa-solid fa-users w-4 text-center"></i> Tenant
                    </a>

                    <a href="/kost-management/admin/users/index.php" 
                       class="flex items-center gap-2 rounded-lg px-3.5 py-2 transition <?= str_contains($current_page, 'admin/users/') ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>">
                        <i class="fa-solid fa-user-check w-4 text-center"></i> Verifikasi
                    </a>

                    <a href="/kost-management/admin/payments/index.php" 
                       class="flex items-center gap-2 rounded-lg px-3.5 py-2 transition <?= str_contains($current_page, 'admin/payments/') ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>">
                        <i class="fa-solid fa-wallet w-4 text-center"></i> Pembayaran
                    </a>

                <?php else: ?>

                    <a href="/kost-management/tenant/dashboard.php" 
                       class="flex items-center gap-2 rounded-lg px-3.5 py-2 transition <?= str_contains($current_page, 'tenant/dashboard.php') ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>">
                        <i class="fa-solid fa-chart-line w-4 text-center"></i> Dashboard
                    </a>

                    <a href="/kost-management/tenant/profile.php" 
                       class="flex items-center gap-2 rounded-lg px-3.5 py-2 transition <?= str_contains($current_page, 'tenant/profile.php') ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>">
                        <i class="fa-solid fa-user-pen w-4 text-center"></i> Profil Saya
                    </a>

                <?php endif; ?>

                <!-- Divider & User Profile Info / Logout -->
                <div class="ml-2 pl-3 border-l border-slate-200 flex items-center gap-3">
                    <div class="flex items-center gap-2.5 bg-slate-50 border border-slate-200/80 px-3 py-1.5 rounded-xl">
                        <div class="h-7 w-7 rounded-full bg-slate-200 text-slate-700 font-bold flex items-center justify-center text-xs">
                            <?= strtoupper(substr($user['name'] ?? $user['username'] ?? 'U', 0, 1)) ?>
                        </div>
                        <div class="text-left leading-tight">
                            <span class="block text-xs font-bold text-slate-800 truncate max-w-[100px]"><?= htmlspecialchars($user['name'] ?? $user['username'] ?? 'User') ?></span>
                            <span class="block text-[10px] text-indigo-600 font-medium uppercase tracking-wider"><?= $user['role'] ?></span>
                        </div>
                    </div>

                    <a href="/kost-management/actions/logout.php" 
                       class="flex items-center justify-center h-9 w-9 rounded-xl text-slate-500 hover:bg-red-50 hover:text-red-600 transition" title="Keluar">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </a>
                </div>

            <?php else: ?>

                <!-- Guest Buttons -->
                <div class="ml-2 flex items-center gap-2">
                    <a href="/kost-management/public/login.php" 
                       class="rounded-xl px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                        Login
                    </a>

                    <a href="/kost-management/public/register.php" 
                       class="rounded-xl bg-indigo-600 px-4 py-2 font-medium text-white shadow-sm shadow-indigo-200 transition hover:bg-indigo-700 active:scale-95">
                        Daftar
                    </a>
                </div>

            <?php endif; ?>

        </div>

        <!-- Mobile Menu Button (Hamburger) -->
        <div class="flex md:hidden items-center">
            <button id="mobile-menu-button" class="rounded-xl p-2 text-slate-600 hover:bg-slate-100 transition focus:outline-hidden">
                <i class="fa-solid fa-bars text-xl"></i>
            </button>
        </div>
    </div>

    <!-- Mobile Dropdown Menu -->
    <div id="mobile-menu" class="hidden md:hidden border-t border-slate-200 bg-white px-4 pt-3 pb-5 space-y-1.5 shadow-lg">
        <?php if (!$user || $user['role'] !== 'admin'): ?>
            <a href="/kost-management/public/rooms.php" class="flex items-center gap-3 rounded-lg px-3.5 py-2.5 text-sm text-slate-600 hover:bg-slate-100">
                <i class="fa-solid fa-door-open w-5 text-center text-indigo-600"></i> Kamar
            </a>
        <?php endif; ?>

        <?php if ($user): ?>
            <?php if ($user['role'] === 'admin'): ?>
                <a href="/kost-management/admin/dashboard.php" class="flex items-center gap-3 rounded-lg px-3.5 py-2.5 text-sm text-slate-600 hover:bg-slate-100">
                    <i class="fa-solid fa-chart-pie w-5 text-center text-indigo-600"></i> Dashboard
                </a>
                <a href="/kost-management/admin/rooms/index.php" class="flex items-center gap-3 rounded-lg px-3.5 py-2.5 text-sm text-slate-600 hover:bg-slate-100">
                    <i class="fa-solid fa-hotel w-5 text-center text-indigo-600"></i> Kamar
                </a>
                <a href="/kost-management/admin/tenants/index.php" class="flex items-center gap-3 rounded-lg px-3.5 py-2.5 text-sm text-slate-600 hover:bg-slate-100">
                    <i class="fa-solid fa-users w-5 text-center text-indigo-600"></i> Tenant
                </a>
                <a href="/kost-management/admin/users/index.php" class="flex items-center gap-3 rounded-lg px-3.5 py-2.5 text-sm text-slate-600 hover:bg-slate-100">
                    <i class="fa-solid fa-user-check w-5 text-center text-indigo-600"></i> Verifikasi User
                </a>
                <a href="/kost-management/admin/payments/index.php" class="flex items-center gap-3 rounded-lg px-3.5 py-2.5 text-sm text-slate-600 hover:bg-slate-100">
                    <i class="fa-solid fa-wallet w-5 text-center text-indigo-600"></i> Pembayaran
                </a>
            <?php else: ?>
                <a href="/kost-management/tenant/dashboard.php" class="flex items-center gap-3 rounded-lg px-3.5 py-2.5 text-sm text-slate-600 hover:bg-slate-100">
                    <i class="fa-solid fa-chart-line w-5 text-center text-indigo-600"></i> Dashboard
                </a>
                <a href="/kost-management/tenant/profile.php" class="flex items-center gap-3 rounded-lg px-3.5 py-2.5 text-sm text-slate-600 hover:bg-slate-100">
                    <i class="fa-solid fa-user-pen w-5 text-center text-indigo-600"></i> Profil Saya
                </a>
            <?php endif; ?>

            <div class="pt-2 mt-2 border-t border-slate-200">
                <a href="/kost-management/actions/logout.php" class="flex items-center gap-3 rounded-lg px-3.5 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50">
                    <i class="fa-solid fa-arrow-right-from-bracket w-5 text-center"></i> Keluar
                </a>
            </div>
        <?php else: ?>
            <div class="pt-2 space-y-2">
                <a href="/kost-management/public/login.php" class="block w-full text-center rounded-xl border border-slate-300 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Login
                </a>
                <a href="/kost-management/public/register.php" class="block w-full text-center rounded-xl bg-indigo-600 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                    Daftar
                </a>
            </div>
        <?php endif; ?>
    </div>
</nav>

<!-- Script kecil untuk interaksi menu mobile -->
<script>
    const btn = document.getElementById('mobile-menu-button');
    const menu = document.getElementById('mobile-menu');

    btn.addEventListener('click', () => {
        menu.classList.toggle('hidden');
    });
</script>