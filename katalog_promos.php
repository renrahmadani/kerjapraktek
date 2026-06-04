<?php
require_once 'config.php';

// Cek session untuk memanipulasi Navbar icon
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_logged_in = isset($_SESSION['user_id']);

// Fetch Semua Promos
$promos = [];
try {
    $stmt = $pdo->query("SELECT * FROM promos ORDER BY id DESC");
    $promos = $stmt->fetchAll();
} catch (PDOException $e) { /* ignore */ }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Promosi - Wahana Indo Trada</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="display: flex; flex-direction: column; min-height: 100vh;">

    <!-- Top Navigation Bar -->
    <nav class="navbar">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <div class="nav-brand" style="cursor:pointer;" onclick="window.location.href='index.php'">
                <img src="logo.png" alt="PT. Wahana Indo Trada" style="height: 35px; width: auto;" onerror="this.onerror=null; this.src=''; this.alt='PT. Wahana Indo Trada'; this.style.fontSize='1.2rem'; this.style.fontWeight='900'; this.style.color='var(--primary)';">
            </div>

            <div class="nav-links">
                <a href="index.php">Services</a>
                <a href="booking.php">My Bookings</a>
                <a href="katalog_promos.php" class="active">Promos</a>
                <a href="detail.php">Detail</a>
                <a href="profile.php">Profile</a>
            </div>

            <div class="nav-actions">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <button class="btn-primary" style="background-color: var(--error); border-color: var(--error);" onclick="confirmLogoutUser(event, 'auth.php?action=logout')">Logout</button>
                <?php else: ?>
                    <button class="btn-primary" onclick="window.location.href='auth.php'">Login</button>
                <?php endif; ?>
                <button class="material-symbols-outlined" style="position:relative;" onclick="window.location.href='notifications.php'">
                    notifications
                    <?php if($unread_notifs > 0): ?><span style="position:absolute; top:-2px; right:-2px; background:var(--error); color:white; border-radius:50%; font-size:0.65rem; width:16px; height:16px; display:flex; align-items:center; justify-content:center; font-family:sans-serif; font-weight:bold;"><?= $unread_notifs ?></span><?php endif; ?>
                </button>
                <?php if($is_logged_in): ?>
                    <button class="material-symbols-outlined" onclick="window.location.href='profile.php'">account_circle</button>
                <?php else: ?>
                    <button class="material-symbols-outlined" onclick="window.location.href='auth.php'">account_circle</button>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main style="padding-top: 6rem; flex: 1;">
        <section class="section">
            <div class="container">
                <div class="promos-head" style="margin-bottom: 3rem;">
                    <div>
                        <h1 style="font-family: var(--font-headline); font-size: 2.5rem; font-weight: 900; color: var(--primary); margin-bottom: 0.5rem;">Katalog Promosi Penuh</h1>
                        <p style="color: var(--on-surface-variant); font-size: 1.1rem;">Temukan ragam diskon besar-besaran untuk kendaraan kesayangan Anda.</p>
                    </div>
                </div>

                <div class="promo-grid">
                    <?php if (empty($promos)): ?>
                        <p style="text-align:center; min-height:50vh;">Maaf, sedang tidak ada promo yang berjalan saat ini.</p>
                    <?php else: ?>
                        <?php foreach($promos as $promo): ?>
                        <div class="promo-card">
                            <div class="promo-badge <?= $promo['badge_type'] == 'soon' ? 'soon' : '' ?>">
                                <?php if($promo['badge_type'] != 'soon'): ?>
                                <span class="badge-dot" style="animation: pulse 2s infinite;"></span>
                                <?php endif; ?>
                                <?= htmlspecialchars($promo['badge_text']) ?>
                            </div>
                            <?php 
                                $bg_style = !empty($promo['image_url']) 
                                    ? "background: url('".htmlspecialchars($promo['image_url'])."') center/cover;" 
                                    : "background: linear-gradient(135deg, ".htmlspecialchars($promo['bg_gradient_start']).", ".htmlspecialchars($promo['bg_gradient_end']).");";
                            ?>
                            <div class="promo-visual" style="<?= $bg_style ?> <?= $promo['badge_type'] == 'soon' ? 'filter: grayscale(80%); transition: filter 0.3s;' : '' ?>" onmouseover="this.style.filter='grayscale(0%)'" onmouseout="this.style.filter='<?= $promo['badge_type'] == 'soon' ? 'grayscale(80%)' : 'none' ?>'">
                                <span><?= htmlspecialchars($promo['discount_text']) ?></span>
                            </div>
                            <div class="promo-body">
                                <h4 class="promo-title"><?= htmlspecialchars($promo['title']) ?></h4>
                                <p class="promo-desc"><?= htmlspecialchars($promo['description']) ?></p>
                                <span class="promo-date"><?= htmlspecialchars($promo['valid_until']) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container footer-content">
            <div class="nav-brand" style="display:flex; align-items:center;">
                <img src="logo.png" alt="PT. Wahana Indo Trada" style="height: 30px; width: auto; filter: grayscale(100%) opacity(0.7);" onerror="this.onerror=null; this.src=''; this.alt='PT. Wahana Indo Trada';">
            </div>
            <p style="color: #94a3b8; font-size: 0.85rem; max-width: 300px; margin-top: 1rem;">
                © 2023 PT. Wahana Indo Trada. All rights reserved.
            </p>
        </div>
    </footer>

    <!-- Mobile Navigation -->
    <div class="mobile-bottom-nav">
        <a href="index.php" class="mobile-nav-item" style="text-decoration: none;">
            <span class="material-symbols-outlined">home</span>
            <span>Home</span>
        </a>
        <a href="booking.php" class="mobile-nav-item" style="text-decoration: none;">
            <span class="material-symbols-outlined">event_note</span>
            <span>Bookings</span>
        </a>
        <a href="katalog_promos.php" class="mobile-nav-item active" style="text-decoration: none;">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">local_offer</span>
            <span>Promos</span>
        </a>
        <a href="detail.php" class="mobile-nav-item" style="text-decoration: none;">
            <span class="material-symbols-outlined">info</span>
            <span>Detail</span>
        </a>
        <a href="<?= $is_logged_in ? 'profile.php' : 'auth.php' ?>" class="mobile-nav-item" style="text-decoration: none;">
            <span class="material-symbols-outlined">person</span>
            <span>Profile</span>
        </a>
    </div>

    <!-- FAB WhatsApp -->
    <a href="https://wa.me/<?= WA_NUMBER ?>?text=Halo%20Bengkel%20Wahana%20Indo%20Trada.%20Saya%20ingin%20bertanya%20seputar%20servis." target="_blank" class="fab-wa" aria-label="Chat via WhatsApp">
        <svg fill="currentColor" height="28" viewBox="0 0 24 24" width="28">
            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.347-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.876 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"></path>
        </svg>
    </a>

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
