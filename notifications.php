<?php
require_once 'config.php';

// Periksa session global
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo "<!DOCTYPE html><html><head><meta name='viewport' content='width=device-width, initial-scale=1.0'><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head>
    <body style='background:#f8fafc;'><script>
        Swal.fire({
            icon: 'warning',
            title: 'Harap Login',
            text: 'Silakan login terlebih dahulu untuk melihat notifikasi.',
            confirmButtonColor: '#3b82f6'
        }).then(() => { window.location.href='auth.php'; });
    </script></body></html>";
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'customer';

// Tandai semua dibaca jika diklik 'Mark all as read'
if (isset($_GET['mark_read'])) {
    if ($role === 'admin') {
        $pdo->query("UPDATE notifications SET is_read = 1 WHERE user_id IS NULL");
    } else {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }
    header("Location: notifications.php");
    exit;
}

// Tandai dibaca individual
if (isset($_GET['read_id'])) {
    $r_id = $_GET['read_id'];
    if ($role === 'admin') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id IS NULL");
        $stmt->execute([$r_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$r_id, $user_id]);
    }
    header("Location: notifications.php");
    exit;
}

// Ambil notifikasi
$notifs = [];
if ($role === 'admin') {
    $stmt = $pdo->query("SELECT * FROM notifications WHERE user_id IS NULL ORDER BY created_at DESC LIMIT 50");
    $notifs = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$user_id]);
    $notifs = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Notifikasi - Wahana Indo Trada</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .notif-container { max-width: 800px; margin: 4rem auto; padding: 0 1rem; }
        .notif-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .notif-header h1 { font-family: var(--font-headline); font-size: 2rem; color: var(--primary); margin: 0; }
        .btn-mark-read { color: var(--primary); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-mark-read:hover { text-decoration: underline; }
        
        .notif-list { display: flex; flex-direction: column; gap: 1rem; }
        .notif-item { padding: 1.5rem; background: var(--surface); border-radius: 1rem; box-shadow: var(--shadow-sm); display: flex; gap: 1rem; align-items: flex-start; transition: all 0.3s; }
        .notif-item.unread { border-left: 4px solid var(--primary); background: var(--surface-variant); }
        .notif-icon { width: 40px; height: 40px; border-radius: 50%; background: var(--primary-container); color: var(--primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .notif-body { flex: 1; }
        .notif-title { font-weight: 700; margin: 0 0 0.25rem 0; color: var(--on-surface); }
        .notif-msg { margin: 0 0 0.5rem 0; color: var(--on-surface-variant); line-height: 1.5; }
        .notif-time { font-size: 0.85rem; color: #888; }
        
        .btn-back { display: inline-block; margin-bottom: 1.5rem; color: var(--on-surface); text-decoration: none; font-weight: 600; }
        .btn-back:hover { color: var(--primary); }
    </style>
</head>
<body style="background: var(--background);">

<?php if ($role !== 'admin'): ?>
    <nav class="navbar">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <div class="nav-brand" style="cursor: pointer;" onclick="window.location.href='index.php'">
                <img src="logo.png" alt="PT. Wahana Indo Trada" style="height: 35px; width: auto;" onerror="this.onerror=null; this.src=''; this.alt='PT. Wahana Indo Trada'; this.style.fontSize='1.2rem'; this.style.fontWeight='900'; this.style.color='var(--primary)';">
            </div>

            <div class="nav-links">
                <a href="index.php">Services</a>
                <a href="booking.php">My Bookings</a>
                <a href="katalog_promos.php">Promos</a>
                <a href="detail.php">Detail</a>
                <a href="profile.php">Profile</a>
            </div>

            <div class="nav-actions">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <button class="btn-primary" style="background-color: var(--error); border-color: var(--error);" onclick="confirmLogoutUser(event, 'auth.php?action=logout')">Logout</button>
                <?php else: ?>
                    <button class="btn-primary" onclick="window.location.href='auth.php'">Login</button>
                <?php endif; ?>
                <button class="material-symbols-outlined" style="color:var(--primary);">notifications</button>
                <button class="material-symbols-outlined" onclick="window.location.href='profile.php'">account_circle</button>
            </div>
        </div>
    </nav>
<?php endif; ?>

<main class="notif-container">
    <?php if ($role === 'admin'): ?>
        <a href="admin/dashboard.php" class="btn-back">← Kembali ke Dashboard Admin</a>
    <?php endif; ?>

    <div class="notif-header">
        <h1>Inbox Notifikasi</h1>
        <?php if (!empty($notifs)): ?>
            <a href="?mark_read=1" class="btn-mark-read">
                <span class="material-symbols-outlined" style="font-size: 1.25rem;">done_all</span> Tandai semua dibaca
            </a>
        <?php endif; ?>
    </div>

    <div class="notif-list">
        <?php if(empty($notifs)): ?>
            <div style="text-align: center; padding: 4rem 1rem; color: #888;">
                <span class="material-symbols-outlined" style="font-size: 4rem; opacity: 0.5; margin-bottom: 1rem;">notifications_off</span>
                <p>Belum ada notifikasi baru untuk Anda.</p>
            </div>
        <?php else: ?>
            <?php foreach($notifs as $n): ?>
                <div class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>">
                    <div class="notif-icon">
                        <span class="material-symbols-outlined text-primary">info</span>
                    </div>
                    <div class="notif-body">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                            <h4 class="notif-title"><?= htmlspecialchars($n['title']) ?></h4>
                            <?php if(!$n['is_read']): ?>
                                <a href="?read_id=<?= $n['id'] ?>" title="Tandai dibaca" style="color:var(--primary); text-decoration:none;">
                                    <span class="material-symbols-outlined" style="font-size: 1.25rem;">check_circle</span>
                                </a>
                            <?php endif; ?>
                        </div>
                        <p class="notif-msg"><?= htmlspecialchars($n['message']) ?></p>
                        <span class="notif-time"><?= date('d M Y, H:i', strtotime($n['created_at'])) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php if ($role !== 'admin'): ?>
    <!-- FAB WhatsApp -->
    <a href="https://wa.me/<?= WA_NUMBER ?>?text=Halo%20Bengkel%20Wahana%20Indo%20Trada.%20Saya%20ingin%20bertanya%20seputar%20servis." target="_blank" class="fab-wa" aria-label="Chat via WhatsApp">
        <svg fill="currentColor" height="28" viewBox="0 0 24 24" width="28">
            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.347-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.876 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"></path>
        </svg>
    </a>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmLogoutUser(event, url) {
    event.preventDefault();
    Swal.fire({
        title: 'Konfirmasi Logout',
        text: 'Apakah Anda yakin ingin keluar?',
        icon: 'warning',
        heightAuto: false,
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
