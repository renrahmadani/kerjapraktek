<?php
session_start();
require_once '../config.php';

// Proteksi Halaman: Hanya admin yang boleh masuk
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Arahkan ke halaman login (bisa dikustomisasi)
    echo "<script>alert('Akses Ditolak: Anda bukan Admin!'); window.location.href='../auth.php';</script>";
    exit;
}

$admin_name = $_SESSION['fullname'] ?? 'Admin';

// Query Statistik
try {
    // Booking Hari Ini
    $stmtToday = $pdo->query("SELECT COUNT(*) FROM bookings WHERE tgl_booking = CURDATE()");
    $countToday = $stmtToday->fetchColumn();

    // Dalam Proses
    $stmtProcess = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'Proses'");
    $countProcess = $stmtProcess->fetchColumn();

    // Selesai Bulan Ini
    $stmtDone = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'Selesai' AND MONTH(tgl_booking) = MONTH(CURDATE()) AND YEAR(tgl_booking) = YEAR(CURDATE())");
    $countDone = $stmtDone->fetchColumn();

    // Promo Aktif
    $stmtPromo = $pdo->query("SELECT COUNT(*) FROM promos WHERE badge_type = 'active'");
    $countPromo = $stmtPromo->fetchColumn();

    // Ambil Data Booking Terbaru
    $stmtBookings = $pdo->query("SELECT * FROM bookings ORDER BY tgl_booking DESC, jam_booking DESC LIMIT 5");
    $recentBookings = $stmtBookings->fetchAll();

} catch (PDOException $e) {
    // Abaikan jika tabel belum ada
    $countToday = $countProcess = $countDone = $countPromo = 0;
    $recentBookings = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PT. Wahana Indo Trada</title>
    <link rel="stylesheet" href="../style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="dashboard-layout">
    
    <!-- Sidebar -->
    <nav class="sidebar">
        <!-- Header -->
        <div class="sidebar-header" style="cursor: pointer;" onclick="window.location.href='../index.php'">
            <div class="sidebar-avatar">
                <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuBEFEvrc9N8gbQGXW4ldmtTaLZ3drxIRCSC4Cza4qUorQCNUy8LvLDeo0d5GgVGJSAbH_2EWOKT7N6XPWlJQRJ8VHyCNc8i-OIJ0ESWLnu7JCTFwRycxUgEk6hfZ0_jojkLo21s5W5SVO-CK_v1dY0Y2Q3xHnfk2oLpp8JPd4_IjxdHubT3ouInkD53hZ-orKvJdoVxjnOiIUfTbm0_QlRaaOnEWSTfmlAF5WA9mUtQJP9MTuYbs18XH9bM9aM5zOGcwrW6Zxbd7-_T" alt="Admin Profile">
            </div>
            <div>
                <h2 class="sidebar-title">Service Console</h2>
                <p class="sidebar-subtitle">PT. Wahana Indo Trada</p>
            </div>
        </div>

        <!-- Navigation Links -->
        <ul class="sidebar-nav">
            <li>
                <a href="dashboard.php" class="active">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">dashboard</span>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="bookings.php">
                    <span class="material-symbols-outlined">calendar_month</span>
                    Bookings
                </a>
            </li>
            <li>
                <a href="promos.php">
                    <span class="material-symbols-outlined">sell</span>
                    Promotions
                </a>
            </li>
            <li>
                <a href="customers.php">
                    <span class="material-symbols-outlined">group</span>
                    Customers
                </a>
            </li>
            <li>
                <a href="reviews.php">
                    <span class="material-symbols-outlined">reviews</span>
                    Reviews
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <ul class="sidebar-nav" style="gap:0;">
                <li>
                    <a href="profile.php">
                        <span class="material-symbols-outlined">person</span>
                        Profil Admin
                    </a>
                </li>
                <li>
                    <!-- Hapus Session/Logout -->
                    <a href="#" onclick="confirmLogout(event, '../auth.php?action=logout');">
                        <span class="material-symbols-outlined">logout</span>
                        Logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="dashboard-main">
        
        <!-- Header Top Bar -->
        <header class="dashboard-topbar">
            <div class="topbar-title">
                <h1>Overview</h1>
                <p>Welcome back to your dashboard.</p>
            </div>
            
            <div class="topbar-actions">
                <button class="material-symbols-outlined" style="position:relative; border:none; background:none; cursor:pointer; color:var(--on-surface-variant); margin-right:1rem; font-size:1.5rem;" onclick="window.location.href='../notifications.php'">
                    notifications
                    <?php if(isset($unread_notifs) && $unread_notifs > 0): ?><span style="position:absolute; top:-2px; right:-2px; background:var(--error); color:white; border-radius:50%; font-size:0.6rem; width:14px; height:14px; display:flex; align-items:center; justify-content:center; font-family:sans-serif; font-weight:bold;"><?= $unread_notifs ?></span><?php endif; ?>
                </button>
                <div class="topbar-profile">
                    <a href="profile.php" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                        <span class="topbar-profile-name">Hi, <?= htmlspecialchars($admin_name) ?></span>
                        <span class="material-symbols-outlined" style="color:var(--primary);">account_circle</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            
            <!-- Stats Grid -->
            <section class="stats-grid">
                
                <!-- Stat Card 1 -->
                <div class="stat-card">
                    <div class="stat-icon-bg">
                        <span class="material-symbols-outlined" style="color: var(--primary);">today</span>
                    </div>
                    <div>
                        <p class="stat-label">Booking Hari Ini</p>
                        <h3 class="stat-value"><?= $countToday ?></h3>
                    </div>
                    <div class="stat-footer positive">
                        <span class="material-symbols-outlined" style="font-size: 16px;">trending_up</span>
                        <span>+2 from yesterday</span>
                    </div>
                </div>

                <!-- Stat Card 2 -->
                <div class="stat-card">
                    <div class="stat-icon-bg">
                        <span class="material-symbols-outlined" style="color: var(--secondary);">autorenew</span>
                    </div>
                    <div>
                        <p class="stat-label">Dalam Proses</p>
                        <h3 class="stat-value"><?= $countProcess ?></h3>
                    </div>
                    <div class="stat-footer warning">
                        <span class="material-symbols-outlined" style="font-size: 16px;">pending</span>
                        <span>Needs attention</span>
                    </div>
                </div>

                <!-- Stat Card 3 -->
                <div class="stat-card">
                    <div class="stat-icon-bg">
                        <span class="material-symbols-outlined" style="color: var(--primary);">task_alt</span>
                    </div>
                    <div>
                        <p class="stat-label">Selesai Bulan Ini</p>
                        <h3 class="stat-value"><?= $countDone ?></h3>
                    </div>
                    <div class="stat-footer neutral">
                        <span class="material-symbols-outlined" style="font-size: 16px;">calendar_month</span>
                        <span><?= date('F Y') ?></span>
                    </div>
                </div>

                <!-- Stat Card 4 -->
                <div class="stat-card">
                    <div class="stat-icon-bg">
                        <span class="material-symbols-outlined" style="color: var(--primary);">sell</span>
                    </div>
                    <div>
                        <p class="stat-label">Promo Aktif</p>
                        <h3 class="stat-value"><?= $countPromo ?></h3>
                    </div>
                    <div class="stat-footer neutral">
                        <span style="color: inherit; text-decoration: none; cursor: default;">Promo Aktif Tersedia</span>
                    </div>
                </div>

            </section>

            <!-- Recent Bookings Section -->
            <section class="table-card">
                <div class="table-header">
                    <h2>Recent Bookings</h2>
                    <a href="bookings.php">
                        View All <span class="material-symbols-outlined" style="font-size: 1rem;">arrow_forward</span>
                    </a>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Layanan</th>
                                <th>Tgl</th>
                                <th>Status</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($recentBookings)): ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; padding: 2rem;">Belum ada data booking terbaru.</td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $colorClasses = ['avatar-1', 'avatar-2', 'avatar-3', 'avatar-default'];
                                foreach($recentBookings as $index => $bk): 
                                    $avatarClass = $colorClasses[$index % 4];
                                    $badgeClass = 'badge-baru';
                                    if ($bk['status'] === 'Proses') $badgeClass = 'badge-proses';
                                    if ($bk['status'] === 'Selesai') $badgeClass = 'badge-selesai';
                                ?>
                                <tr>
                                    <td style="color: var(--on-surface-variant); font-weight: 500; font-family: var(--font-headline);">
                                        <?= htmlspecialchars($bk['booking_code']) ?>
                                    </td>
                                    <td>
                                        <div class="table-customer">
                                            <div class="customer-avatar <?= $avatarClass ?>">
                                                <?= htmlspecialchars($bk['customer_initials']) ?>
                                            </div>
                                            <span style="font-weight: 600; color: var(--primary);">
                                                <?= htmlspecialchars($bk['customer_name']) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($bk['service_name']) ?></td>
                                    <td style="color: var(--on-surface-variant);">
                                        <?= date('d M Y', strtotime($bk['tgl_booking'])) ?>, <?= substr($bk['jam_booking'], 0, 5) ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $badgeClass ?>">
                                            <?= htmlspecialchars($bk['status']) ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <button class="btn-action" style="background:none; border:none; cursor:pointer;">
                                            <span class="material-symbols-outlined">more_vert</span>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </main>

<script>
function confirmLogout(event, url) {
    event.preventDefault();
    Swal.fire({
        title: 'Konfirmasi Logout',
        text: 'Apakah Anda yakin ingin keluar dari sesi admin?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Logout',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}
</script>
</body>
</html>
