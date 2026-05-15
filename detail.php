<?php
session_start();
require_once 'config.php';

$title = 'Detail Layanan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Wahana Indo Trada</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="display: flex; flex-direction: column; min-height: 100vh;">

    <!-- Top Navigation Bar -->
    <nav class="navbar">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <div class="nav-brand" style="cursor: pointer;" onclick="window.location.href='index.php'">
                <img src="logo.png" alt="PT. Wahana Indo Trada" style="height: 35px; width: auto;" onerror="this.onerror=null; this.src=''; this.alt='PT. Wahana Indo Trada'; this.style.color='var(--primary)'; this.style.fontWeight='900';">
            </div>
            
            <div class="nav-links">
                <a href="index.php">Services</a>
                <a href="booking.php">My Bookings</a>
                <a href="katalog_promos.php">Promos</a>
                <a href="detail.php" class="active">Detail</a>
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
    <header class="hero" style="min-height: 40vh; padding-top: 8rem; padding-bottom: 4rem;">
        <!-- Background gabungan atau netral -->
        <div class="hero-bg" style="background-image: url('assets/auth_bg.png'); filter: brightness(0.35);"></div>
        
        <div class="container hero-content" style="position: relative; z-index: 2; text-align: center;">
            <div class="hero-badge" style="margin: 0 auto 1.5rem auto;">
                <span class="badge-dot"></span>
                Pusat Informasi
            </div>
            <h1 style="color: white; font-family: var(--font-headline); font-size: 3rem; margin-bottom: 1rem;">Layanan Kami</h1>
            <p style="color: var(--surface-variant); font-size: 1.2rem; max-width: 600px; margin: 0 auto;">
                Temukan solusi terbaik untuk kendaraan Anda. Mulai dari perbaikan mesin hingga restorasi bodi kendaraan.
            </p>
        </div>
    </header>

    <main class="container" style="flex: 1; padding: 4rem 1rem;">
        
        <!-- General Repair Section -->
        <div style="background: var(--surface); padding: 3rem; border-radius: 1rem; box-shadow: var(--shadow-sm); max-width: 900px; margin: 0 auto 3rem auto; line-height: 1.8;">
            <h2 style="color: var(--primary); font-family: var(--font-headline); margin-bottom: 1rem;">⚙️ General Repair</h2>
            <img src="assets/general_repair.png" alt="General Repair" style="width: 100%; height: 300px; object-fit: cover; border-radius: 0.5rem; margin-bottom: 1.5rem;">
            <p>
                <strong>General Repair</strong> mencakup semua layanan perawatan mekanis dan elektrikal untuk memastikan kendaraan Anda beroperasi pada performa maksimal. Kami menggunakan peralatan diagnostik terbaru untuk mendeteksi dan menyelesaikan masalah secara akurat.
            </p>
            <h3 style="margin-top: 2rem; margin-bottom: 1rem; color: var(--on-surface);">Layanan yang Tersedia:</h3>
            <ul style="list-style-type: disc; margin-left: 2rem; color: var(--on-surface-variant);">
                <li>Servis Berkala & Penggantian Oli (Mesin, Transmisi, Gardan)</li>
                <li>Pengecekan dan Perbaikan Sistem Pengereman (Kampas Rem, Minyak Rem, Cakram)</li>
                <li>Perawatan Sistem Suspensi dan Kemudi (Shockbreaker, Tie Rod, Ball Joint)</li>
                <li>Tune-Up Mesin dan Pembersihan Injektor</li>
                <li>Perbaikan Sistem Kelistrikan dan AC Kendaraan</li>
                <li>Pengecekan Aki dan Sistem Starter</li>
            </ul>
            <div style="margin-top: 2rem; padding: 1.5rem; background: var(--primary-container); border-radius: 0.5rem; color: var(--on-primary-container);">
                <h4 style="margin-bottom: 0.5rem;"><span class="material-symbols-outlined" style="vertical-align: middle;">verified</span> Keunggulan Kami</h4>
                <p style="margin: 0;">Suku cadang yang kami gunakan adalah original (genuine parts) dan setiap pengerjaan didukung oleh garansi servis.</p>
            </div>
            <div style="text-align: right; margin-top: 2rem;">
                <button class="btn-primary" onclick="window.location.href='booking.php'">Booking General Repair</button>
            </div>
        </div>

        <hr style="border: 0; height: 1px; background: var(--outline-variant); margin: 3rem 0;">

        <!-- Body Repair Section -->
        <div style="background: var(--surface); padding: 3rem; border-radius: 1rem; box-shadow: var(--shadow-sm); max-width: 900px; margin: 0 auto; line-height: 1.8;">
            <h2 style="color: var(--primary); font-family: var(--font-headline); margin-bottom: 1rem;">🚘 Body Repair</h2>
            <!-- Changed the body repair image to a very stable automotive painting Unsplash image -->
            <img src="assets/body_repair.png" alt="Body Repair" style="width: 100%; height: 300px; object-fit: cover; border-radius: 0.5rem; margin-bottom: 1.5rem;">
            <p>
                <strong>Body Repair</strong> adalah layanan pemulihan struktur dan estetika kendaraan Anda. Baik dari goresan kecil hingga perbaikan pasca tabrakan skala besar, tim kami siap mengembalikan kondisi mobil Anda seperti baru keluar dari dealer.
            </p>
            <h3 style="margin-top: 2rem; margin-bottom: 1rem; color: var(--on-surface);">Layanan yang Tersedia:</h3>
            <ul style="list-style-type: disc; margin-left: 2rem; color: var(--on-surface-variant);">
                <li>Perbaikan Penyok (Denton) dan Goresan Bodi Kendaraan</li>
                <li>Penggantian Panel Bodi yang Rusak Parah</li>
                <li>Pengecatan Ulang (Full Body atau Per Panel) dengan Cat Oven Premium</li>
                <li>Ketok Magic Profesional dan Penarikan Sasis (Chassis Pulling)</li>
                <li>Poles Bodi (Polishing) dan Detailing Eksterior</li>
                <li>Nano Ceramic Coating untuk Pelindung Cat Tambahan</li>
            </ul>
            <div style="margin-top: 2rem; padding: 1.5rem; background: var(--tertiary-container); border-radius: 0.5rem; color: var(--on-tertiary-container);">
                <h4 style="margin-bottom: 0.5rem;"><span class="material-symbols-outlined" style="vertical-align: middle;">verified</span> Keunggulan Kami</h4>
                <p style="margin: 0;">Kami menggunakan cat oven standar industri otomotif global dan teknologi color matching yang presisi 99% sama dengan warna asli mobil Anda.</p>
            </div>
            <div style="text-align: right; margin-top: 2rem;">
                <button class="btn-primary" onclick="window.location.href='booking.php'">Booking Body Repair</button>
            </div>
        </div>
    </main>

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
        <a href="katalog_promos.php" class="mobile-nav-item" style="text-decoration: none;">
            <span class="material-symbols-outlined">local_offer</span>
            <span>Promos</span>
        </a>
        <a href="detail.php" class="mobile-nav-item active" style="text-decoration: none;">
            <span class="material-symbols-outlined">info</span>
            <span>Detail</span>
        </a>
        <a href="profile.php" class="mobile-nav-item" style="text-decoration: none;">
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

</body>
</html>
