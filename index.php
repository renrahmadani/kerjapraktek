<?php
require_once 'config.php';

// Fetch Services
$services = [];
try {
    $stmt = $pdo->query("SELECT * FROM services ORDER BY id ASC");
    $services = $stmt->fetchAll();
} catch (PDOException $e) {
    // If table doesn't exist, ignore for now (can be fixed by running init_db.php)
}

// Fetch Promos
$promos = [];
try {
    $stmt = $pdo->query("SELECT * FROM promos ORDER BY id ASC LIMIT 3");
    $promos = $stmt->fetchAll();
} catch (PDOException $e) { /* ignore */ }

// Fetch Reviews
$reviews = [];
try {
    $stmt = $pdo->query("SELECT * FROM reviews WHERE status = 'approved' ORDER BY id DESC LIMIT 10");
    $reviews = $stmt->fetchAll();
} catch (PDOException $e) { /* ignore */ }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wahana Indo Trada - Booking Servis Kendaraan Anda Lebih Mudah & Tanpa Antre</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="display: flex; flex-direction: column; min-height: 100vh;">

    <!-- Top Navigation Bar -->
    <nav class="navbar">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <div class="nav-brand">
                <img src="logo.png" alt="PT. Wahana Indo Trada" style="height: 35px; width: auto;" onerror="this.onerror=null; this.src=''; this.alt='PT. Wahana Indo Trada'; this.style.fontSize='1.2rem'; this.style.fontWeight='900'; this.style.color='var(--primary)';">
            </div>
            
            <div class="nav-links">
                <a href="index.php" class="active">Services</a>
                <a href="booking.php">My Bookings</a>
                <a href="katalog_promos.php">Promos</a>
                <a href="detail.php?kategori=General">Detail</a>
                <a href="profile.php">Profile</a>
            </div>

            <div class="nav-actions">
                <button class="btn-primary" onclick="window.location.href='booking.php'">Book Now</button>
                <button class="material-symbols-outlined" style="position:relative;" onclick="window.location.href='notifications.php'">
                    notifications
                    <?php if($unread_notifs > 0): ?><span style="position:absolute; top:-2px; right:-2px; background:var(--error); color:white; border-radius:50%; font-size:0.65rem; width:16px; height:16px; display:flex; align-items:center; justify-content:center; font-family:sans-serif; font-weight:bold;"><?= $unread_notifs ?></span><?php endif; ?>
                </button>
                <button class="material-symbols-outlined" onclick="window.location.href='profile.php'">account_circle</button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero">
        <div class="hero-bg" style="background-image: url('assets/auth_bg.png');"></div>
        <div class="hero-gradient-overlay"></div>
        
        <div class="container hero-content">
            <!-- Hero Typography -->
            <div class="hero-text">
                <div class="hero-badge">
                    <span class="badge-dot"></span>
                    Premium Service
                </div>
                
                <h1>
                    Booking Servis Kendaraan Anda <br>
                    <span class="highlight">Lebih Mudah &</span> <br>
                    <span class="underline-effect">Tanpa Antre</span>
                </h1>
                
                <p>
                    Experience automotive care elevated to an architectural standard. Precision engineering meets high-end hospitality at Wahana Indo Trada.
                </p>
                
                <div class="hero-buttons">
                    <button class="btn-large primary" onclick="window.location.href='booking.php'">
                        Booking Sekarang
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </button>
                    <button class="btn-large outline" onclick="window.open('https://wa.me/6285591821790?text=Halo%20Bengkel%20Wahana%20Indo%20Trada', '_blank')">
                        Hubungi Kami
                    </button>
                </div>
            </div>

            <!-- Hero Image -->
            <div class="hero-visual">
                <div class="hero-visual-bg"></div>
                <div class="hero-img-wrap">
                    <img src="assets/hero_banner.png" alt="Mechanic inspecting">
                    
                    <div class="glass-card">
                        <div class="glass-icon">
                            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">verified</span>
                        </div>
                        <div>
                            <div style="font-family: var(--font-headline); font-weight: 700; font-size: 1.125rem;">Teknisi Bersertifikat</div>
                            <div style="font-size: 0.875rem; color: var(--on-surface-variant);">Standar Kualitas Tertinggi</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main style="flex: 1;">
        <!-- Layanan Kami Section -->
        <section class="section services-section">
            <div class="container">
                <div class="section-header">
                    <h2>Layanan Kami</h2>
                    <p>Precision automotive care tailored to your vehicle's specific needs.</p>
                </div>

                <div class="services-grid">
                    <?php if (empty($services)): ?>
                        <p>Belum ada data layanan. Silakan jalankan <code>init_db.php</code> terlebih dahulu.</p>
                    <?php else: ?>
                        <?php foreach($services as $sv): 
                            $feats = json_decode($sv['features'], true);
                        ?>
                        <div class="service-card">
                            <div class="service-img">
                                <img src="<?= htmlspecialchars($sv['image_url']) ?>" alt="<?= htmlspecialchars($sv['title']) ?>">
                            </div>
                            <div class="service-body">
                                <div class="service-title">
                                    <span class="material-symbols-outlined text-secondary" style="font-variation-settings: 'FILL' 1;"><?= htmlspecialchars($sv['icon']) ?></span>
                                    <h3><?= htmlspecialchars($sv['title']) ?></h3>
                                </div>
                                
                                <ul class="service-features">
                                    <?php if(is_array($feats)): foreach($feats as $f): ?>
                                    <li>
                                        <span class="material-symbols-outlined">check_circle</span>
                                        <?= htmlspecialchars($f) ?>
                                    </li>
                                    <?php endforeach; endif; ?>
                                </ul>

                                <div class="service-action">
                                    <button class="btn-link" onclick="window.location.href='detail.php'">
                                        Lihat Detail
                                        <span class="material-symbols-outlined">arrow_forward</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Why Us Section -->
        <section class="section features-section">
            <div class="container">
                <div class="section-header" style="text-align: center; margin: 0 auto 4rem auto;">
                    <h2>Mengapa Memilih Kami?</h2>
                    <p>Komitmen kami terhadap keunggulan di setiap sentuhan.</p>
                </div>

                <div class="features-grid">
                    <div class="feature-item">
                        <div class="feature-icon"><span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; font-size: 2rem;">verified</span></div>
                        <h4>Dealer Resmi</h4>
                        <p>Jaminan suku cadang asli dan prosedur standar pabrik.</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; font-size: 2rem;">engineering</span></div>
                        <h4>Teknisi Bersertifikat</h4>
                        <p>Ditangani oleh ahli yang terlatih dan berpengalaman.</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; font-size: 2rem;">devices</span></div>
                        <h4>Booking Online</h4>
                        <p>Jadwalkan servis kapan saja, di mana saja tanpa antre.</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; font-size: 2rem;">payments</span></div>
                        <h4>Harga Transparan</h4>
                        <p>Estimasi biaya yang jelas sebelum pengerjaan dimulai.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Promo Terbaru Section -->
        <section class="section">
            <div class="container">
                <div class="promos-head">
                    <div>
                        <h2 style="font-family: var(--font-headline); font-size: 2.5rem; font-weight: 900; color: var(--primary); margin-bottom: 0.5rem;">Promo Terbaru</h2>
                        <p style="color: var(--on-surface-variant); font-size: 1.1rem;">Penawaran spesial untuk perawatan kendaraan Anda.</p>
                    </div>
                    <a href="katalog_promos.php" class="desktop-see-all btn-link">Lihat Semua <span class="material-symbols-outlined">arrow_forward</span></a>
                </div>

                <div class="promo-grid">
                    <?php if (empty($promos)): ?>
                        <p>Belum ada data promo.</p>
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

                <a href="katalog_promos.php" class="mobile-see-all">Lihat Semua Promo <span class="material-symbols-outlined">arrow_forward</span></a>
            </div>
        </section>

        <!-- Testimonial Section (Carousel) -->
        <section class="testimonial-section" style="padding: 4rem 1rem; overflow: hidden; background: var(--surface-variant);">
            <div class="container">
                <div class="section-header" style="text-align: center; margin-bottom: 3rem;">
                    <h2>Apa Kata Mereka?</h2>
                    <p>Ulasan jujur dari pelanggan setia Wahana Indo Trada.</p>
                </div>

                <?php if (empty($reviews)): ?>
                    <div style="text-align:center; padding:2rem;">Belum ada ulasan yang disetujui.</div>
                <?php else: ?>
                    <div style="display: flex; overflow-x: auto; scroll-snap-type: x mandatory; gap: 2rem; padding-bottom: 2rem; scroll-behavior: smooth; -ms-overflow-style: none; scrollbar-width: none;">
                        <?php foreach($reviews as $rev): ?>
                        <div class="testimonial-content" style="scroll-snap-align: center; flex: 0 0 100%; max-width: 600px; background: var(--surface); padding: 2rem; border-radius: 1rem; box-shadow: var(--shadow-sm); display: flex; flex-direction: column; justify-content: center; color: var(--on-surface);">
                            <div style="display: flex; gap: 0.2rem; color: #f59e0b; margin-bottom: 1rem; justify-content: center;">
                                <?php for($i=0; $i<$rev['rating']; $i++): ?><span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">star</span><?php endfor; ?>
                                <?php for($i=$rev['rating']; $i<5; $i++): ?><span class="material-symbols-outlined">star</span><?php endfor; ?>
                            </div>
                            <span class="material-symbols-outlined quote-icon" style="font-variation-settings: 'FILL' 1; text-align: center; display: block; margin-bottom: 1rem; color: var(--primary);">format_quote</span>
                            <h2 class="testimonial-text" style="font-size: 1.2rem; margin-bottom: 2rem; text-align: center; font-style: italic; color: var(--on-surface);">
                                "<?= htmlspecialchars($rev['comment']) ?>"
                            </h2>
                            <div class="testimonial-author" style="justify-content: center;">
                                <div class="author-avatar" style="background: var(--primary); color: #fff;"><?= htmlspecialchars($rev['customer_initials']) ?></div>
                                <div class="author-info" style="text-align: left;">
                                    <div class="author-name" style="color: var(--on-surface); font-weight: bold;"><?= htmlspecialchars($rev['customer_name']) ?></div>
                                    <div class="author-role" style="color: var(--on-surface-variant);">Pelanggan Bengkel</div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="text-align: center; color: var(--on-surface-variant); font-size: 0.85rem; margin-top: -1rem;">&lt; Geser untuk melihat lebih banyak &gt;</div>
                <?php endif; ?>
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
            </div>
        </div>
    </footer>

    <!-- FAB WhatsApp -->
    <a href="https://wa.me/6285591821790?text=Halo%20Bengkel%20Wahana%20Indo%20Trada.%20Saya%20ingin%20bertanya%20seputar%20servis." target="_blank" class="fab-wa" aria-label="Chat via WhatsApp">
        <svg fill="currentColor" height="28" viewBox="0 0 24 24" width="28">
            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.347-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.876 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"></path>
        </svg>
    </a>

    <!-- Mobile Navigation -->
    <div class="mobile-bottom-nav">
        <button class="mobile-nav-item active" onclick="window.location.href='index.php'">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">home</span>
            <span>Home</span>
        </button>
        <button class="mobile-nav-item" onclick="window.location.href='booking.php'">
            <span class="material-symbols-outlined">event_note</span>
            <span>Bookings</span>
        </button>
        <button class="mobile-nav-item" onclick="window.location.href='katalog_promos.php'">
            <span class="material-symbols-outlined">local_offer</span>
            <span>Promos</span>
        </button>
        <a href="detail.php" class="mobile-nav-item" style="text-decoration: none;">
            <span class="material-symbols-outlined">info</span>
            <span>Detail</span>
        </a>
        <a href="profile.php" class="mobile-nav-item" style="text-decoration: none;">
            <span class="material-symbols-outlined">person</span>
            <span>Profile</span>
        </a>
    </div>

</body>
</html>
