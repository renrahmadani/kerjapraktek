<?php
session_start();
require_once '../config.php';

// Proteksi Halaman
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('Akses Ditolak!'); window.location.href='../auth.php';</script>";
    exit;
}

$admin_name = $_SESSION['fullname'] ?? 'Admin';

// Handle Delete Customer
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    // Hapus customer (Bisa dicegah jika ingin mempertahankan riwayat integrasi tabel child)
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    header("Location: customers.php");
    exit;
}

// Fetch Semua Customers
$customers = [];
try {
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'customer' ORDER BY id DESC");
    $customers = $stmt->fetchAll();
} catch (PDOException $e) { /* ignore */ }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pelanggan - Wahana Indo Trada</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .initials-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; color: white;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }
    </style>
</head>
<body class="dashboard-layout">
    
    <nav class="sidebar">
        <!-- Header -->
        <div class="sidebar-header" style="cursor:pointer;" onclick="window.location.href='../index.php'">
            <div class="sidebar-avatar">
                <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuBEFEvrc9N8gbQGXW4ldmtTaLZ3drxIRCSC4Cza4qUorQCNUy8LvLDeo0d5GgVGJSAbH_2EWOKT7N6XPWlJQRJ8VHyCNc8i-OIJ0ESWLnu7JCTFwRycxUgEk6hfZ0_jojkLo21s5W5SVO-CK_v1dY0Y2Q3xHnfk2oLpp8JPd4_IjxdHubT3ouInkD53hZ-orKvJdoVxjnOiIUfTbm0_QlRaaOnEWSTfmlAF5WA9mUtQJP9MTuYbs18XH9bM9aM5zOGcwrW6Zxbd7-_T" alt="Admin Profile">
            </div>
            <div>
                <h2 class="sidebar-title">Service Console</h2>
                <p class="sidebar-subtitle">PT. Wahana Indo Trada</p>
            </div>
        </div>

        <ul class="sidebar-nav">
            <li><a href="dashboard.php"><span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">dashboard</span>Dashboard</a></li>
            <li><a href="bookings.php"><span class="material-symbols-outlined">calendar_month</span>Bookings</a></li>
            <li><a href="promos.php"><span class="material-symbols-outlined">sell</span>Promotions</a></li>
            <li><a href="customers.php" class="active"><span class="material-symbols-outlined">group</span>Customers</a></li>
            <li><a href="reviews.php"><span class="material-symbols-outlined">reviews</span>Reviews</a></li>
        </ul>

        <div class="sidebar-footer">
            <ul class="sidebar-nav" style="gap:0;">
                <li><a href="../auth.php?action=logout" onclick="return confirm('Logout dari Dashboard?');"><span class="material-symbols-outlined">logout</span>Logout</a></li>
            </ul>
        </div>
    </nav>

    <main class="dashboard-main">
        <header class="dashboard-topbar">
            <div class="topbar-title">
                <h1>Daftar Pelanggan</h1>
                <p>Kelola data registrasi seluruh pelanggan sistem.</p>
            </div>
            <div class="topbar-actions">
                <button class="material-symbols-outlined" style="position:relative; border:none; background:none; cursor:pointer; color:var(--on-surface-variant); margin-right:1rem; font-size:1.5rem;" onclick="window.location.href='../notifications.php'">
                    notifications
                    <?php if($unread_notifs > 0): ?><span style="position:absolute; top:-2px; right:-2px; background:var(--error); color:white; border-radius:50%; font-size:0.6rem; width:14px; height:14px; display:flex; align-items:center; justify-content:center; font-family:sans-serif; font-weight:bold;"><?= $unread_notifs ?></span><?php endif; ?>
                </button>
                <div class="topbar-profile">
                    <span class="topbar-profile-name">Hi, <?= htmlspecialchars($admin_name) ?></span>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <section class="table-card">
                <div class="table-header">
                    <h2>Registrasi Pengguna Sistem (<span style="color:var(--primary);"><?= count($customers) ?></span>)</h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>No. Handphone (WA)</th>
                                <th>Bergabung Sejak</th>
                                <th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($customers)): ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; padding: 2rem;">Belum ada pelanggan terdaftar.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($customers as $idx => $c): 
                                    $words = explode(" ", $c['fullname']);
                                    $ini = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                                ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <div class="table-customer">
                                            <div class="initials-avatar"><?= htmlspecialchars($ini) ?></div>
                                            <span style="font-weight: 600; color: var(--primary);"><?= htmlspecialchars($c['fullname']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($c['username']) ?></td>
                                    <td><?= htmlspecialchars($c['email'] ?? 'Tidak ada data') ?></td>
                                    <td style="font-weight: 500;"><?= htmlspecialchars($c['no_hp'] ?? 'Tidak ada data') ?></td>
                                    <td style="color: var(--on-surface-variant);"><?= date('d M Y, H:i', strtotime($c['created_at'])) ?></td>
                                    <td style="text-align: right;">
                                        <a href="?action=delete&id=<?= $c['id'] ?>" onclick="return confirm('Peringatan: Menghapus pelanggan bisa menghilangkan riwayat pesanan mereka dari layar! Lanjut?')" style="color: #d32f2f; text-decoration: none; font-weight: 600;">Hapus Akun</a>
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

</body>
</html>
