<?php
session_start();
require_once '../config.php';

// Proteksi Halaman
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('Akses Ditolak!'); window.location.href='../auth.php';</script>";
    exit;
}

$admin_name = $_SESSION['fullname'] ?? 'Admin';

// Action Handler
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];
    
    // Ambil data notif
    $stmt = $pdo->prepare("SELECT user_id FROM reviews WHERE id=?");
    $stmt->execute([$id]);
    $rv = $stmt->fetch();
    $target_user_id = $rv['user_id'] ?? null;

    if ($action === 'approve') {
        $pdo->prepare("UPDATE reviews SET status='approved' WHERE id=?")->execute([$id]);
        if($target_user_id) $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Ulasan Disetujui', 'Ulasan Anda telah disetujui Admin dan tampil di halaman utama!')")->execute([$target_user_id]);
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE reviews SET status='rejected' WHERE id=?")->execute([$id]);
        if($target_user_id) $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Ulasan Ditolak', 'Mohon maaf, ulasan Anda melanggar pedoman kami sehingga tidak bisa ditampilkan.')")->execute([$target_user_id]);
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM reviews WHERE id=?")->execute([$id]);
    }
    
    header("Location: reviews.php");
    exit;
}

// Fetch Semua Ulasan
$reviews = [];
try {
    $stmt = $pdo->query("SELECT * FROM reviews ORDER BY id DESC");
    $reviews = $stmt->fetchAll();
} catch (PDOException $e) { /* ignore */ }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderasi Ulasan - Wahana Indo Trada</title>
    <link rel="stylesheet" href="../style.css">
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
            <li><a href="customers.php"><span class="material-symbols-outlined">group</span>Customers</a></li>
            <li><a href="reviews.php" class="active"><span class="material-symbols-outlined">reviews</span>Reviews</a></li>
        </ul>

        <div class="sidebar-footer">
            <ul class="sidebar-nav" style="gap:0;">
                <li><a href="../auth.php?action=logout" onclick="return confirm('Anda yakin ingin logout?');">
                    <span class="material-symbols-outlined">logout</span>Logout
                </a></li>
            </ul>
        </div>
    </nav>

    <main class="dashboard-main">
        <header class="dashboard-topbar">
            <div class="topbar-title">
                <h1>Moderasi Ulasan Publik</h1>
                <p>Filter dan setujui ulasan yang masuk dari pelanggan.</p>
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
                    <h2>Daftar Ulasan Masuk</h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Pengirim (Booking)</th>
                                <th>Rating</th>
                                <th>Komentar</th>
                                <th>Status</th>
                                <th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($reviews)): ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; padding: 2rem;">Belum ada ulasan yang masuk.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($reviews as $r): 
                                    $badge = 'badge-baru';
                                    if($r['status'] == 'approved') $badge = 'badge-selesai';
                                    if($r['status'] == 'rejected') $badge = 'badge-batal';
                                ?>
                                <tr>
                                    <td style="color:var(--on-surface-variant);"><?= date('d M Y, H:i', strtotime($r['created_at'])) ?></td>
                                    <td>
                                        <div style="font-weight:600;"><?= htmlspecialchars($r['customer_name']) ?></div>
                                        <div style="font-size:0.8rem; color:var(--on-surface-variant);">Booking #<?= $r['booking_id'] ?></div>
                                    </td>
                                    <td style="color: #f59e0b;">
                                        <?php for($i=0; $i<$r['rating']; $i++) echo "★"; ?>
                                    </td>
                                    <td style="max-width: 300px; white-space: normal; line-height: 1.4;">
                                        "<?= htmlspecialchars($r['comment']) ?>"
                                    </td>
                                    <td><span class="status-badge <?= $badge ?>"><?= htmlspecialchars(strtoupper($r['status'])) ?></span></td>
                                    <td style="text-align: right;">
                                        <?php if($r['status'] === 'pending'): ?>
                                            <a href="?action=approve&id=<?= $r['id'] ?>" style="color:var(--primary); font-weight:600; text-decoration:none; margin-right:0.5rem;">Terima</a>
                                            <a href="?action=reject&id=<?= $r['id'] ?>" style="color:#d32f2f; font-weight:600; text-decoration:none; margin-right:0.5rem;">Tolak</a>
                                        <?php endif; ?>
                                        <a href="?action=delete&id=<?= $r['id'] ?>" onclick="return confirm('Hapus permanen?')" style="color: #888; text-decoration: none;">Hapus</a>
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
