<?php
session_start();
require_once '../config.php';

// Proteksi Halaman
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('Akses Ditolak!'); window.location.href='../auth.php';</script>";
    exit;
}

$admin_name = $_SESSION['fullname'] ?? 'Admin';

// Action Handler (Update Status & Delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];
    
    // Ambil data user_id dan kode booking untuk keperluan notifikasi
    $stmt = $pdo->prepare("SELECT user_id, booking_code, service_name, kendaraan_details, keluhan, created_at, proses_at, selesai_at, batal_at, tgl_booking, jam_booking FROM bookings WHERE id=?");
    $stmt->execute([$id]);
    $bkData = $stmt->fetch();
    $target_user_id = $bkData['user_id'] ?? null;
    $bk_code = $bkData['booking_code'] ?? 'Unknown';
    $service_name = $bkData['service_name'] ?? '-';
    $keluhan = $bkData['keluhan'] ?? '-';
    $created_at = $bkData['created_at'] ?? null;
    $proses_at = $bkData['proses_at'] ?? null;
    $selesai_at = $bkData['selesai_at'] ?? null;
    $batal_at = $bkData['batal_at'] ?? null;
    $tgl_booking = $bkData['tgl_booking'] ?? date('Y-m-d');
    $jam_booking = $bkData['jam_booking'] ?? '00:00';
    
    $kendaraan_arr = json_decode($bkData['kendaraan_details'] ?? '[]', true);
    $kendaraan_text = "";
    if (is_array($kendaraan_arr)) {
        foreach($kendaraan_arr as $k) {
            $kendaraan_text .= "Nama Kendaraan (Merek/Tipe) : " . $k['nama'] . "\n";
            $kendaraan_text .= "Nomor Polisi : " . $k['plat'] . "\n\n";
        }
    }

    $no_hp = '';
    $cust_email = '';
    $cust_name = 'Pelanggan';
    if ($target_user_id) {
        $stmt_u = $pdo->prepare("SELECT no_hp, email, fullname FROM users WHERE id=?");
        $stmt_u->execute([$target_user_id]);
        $uData = $stmt_u->fetch();
        if ($uData) {
            $no_hp = $uData['no_hp'];
            $cust_email = $uData['email'];
            $cust_name = $uData['fullname'];
            if (substr($no_hp, 0, 1) == '0') {
                $no_hp = '62' . substr($no_hp, 1);
            }
        }
    }

    $wa_link = "";

    $kendaraan_html = "";
    if (is_array($kendaraan_arr)) {
        foreach($kendaraan_arr as $k) {
            $kendaraan_html .= "<tr><td style='padding:8px; border-bottom:1px solid #eee;'><strong>" . htmlspecialchars($k['nama']) . "</strong></td><td style='padding:8px; border-bottom:1px solid #eee;'>" . htmlspecialchars($k['plat']) . "</td></tr>";
        }
    }
    if(empty($kendaraan_html)) {
        $kendaraan_html = "<tr><td colspan='2' style='padding:8px; color:#999;'>Tidak ada data kendaraan</td></tr>";
    }

    // Helper fungsi pembuat template HTML untuk email Customer
    $make_cust_email = function($status_label, $header_bg, $intro_text, $status_color) use ($bk_code, $service_name, $keluhan, $kendaraan_html, $tgl_booking, $jam_booking, $created_at, &$proses_at, &$selesai_at, &$batal_at, $cust_name) {
        $now_str = date('d M Y, H:i');
        
        $c_time = ($created_at) ? date('d M Y, H:i', strtotime($created_at)) : 'Tercatat';
        $p_time = ($proses_at) ? date('d M Y, H:i', strtotime($proses_at)) : ($status_label === 'Proses' ? $now_str : '<span style="color:#94a3b8; font-style:italic;">Menunggu</span>');
        $s_time = ($selesai_at) ? date('d M Y, H:i', strtotime($selesai_at)) : ($status_label === 'Selesai' ? $now_str : '<span style="color:#94a3b8; font-style:italic;">Menunggu</span>');
        $b_time = ($batal_at) ? date('d M Y, H:i', strtotime($batal_at)) : ($status_label === 'Batal' ? $now_str : '-');

        $row3_html = "";
        if ($status_label === 'Batal' || $b_time !== '-') {
            $row3_html = "<tr>
                <td style='padding: 8px; border: 1px solid #fca5a5; font-weight: bold; color: #991b1b; background-color: #fef2f2;' width='40%'>🔴 Dibatalkan / Ditolak</td>
                <td style='padding: 8px; border: 1px solid #fca5a5; color: #991b1b; background-color: #fef2f2;'>$b_time WIB</td>
            </tr>";
        } else {
            $is_selesai = ($status_label === 'Selesai');
            $row3_html = "<tr style='" . ($is_selesai ? "background-color: #ecfdf5;" : "") . "'>
                <td style='padding: 8px; border: 1px solid " . ($is_selesai ? "#a7f3d0" : "#e2e8f0") . "; " . ($is_selesai ? "font-weight:bold; color:#065f46;" : "color:#94a3b8;") . "' width='40%'>" . ($is_selesai ? "🟢 Selesai" : "⚪ Selesai") . "</td>
                <td style='padding: 8px; border: 1px solid " . ($is_selesai ? "#a7f3d0" : "#e2e8f0") . "; " . ($is_selesai ? "color:#065f46;" : "color:#94a3b8;") . "'>" . ($is_selesai ? "$s_time WIB" : $s_time) . "</td>
            </tr>";
        }

        $is_proses = ($status_label === 'Proses');

        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #f8fafc; padding: 20px; border-radius: 8px;'>
            <!-- Header Banner -->
            <div style='background-color: $header_bg; color: #ffffff; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                <h1 style='margin: 0; font-size: 22px; font-weight: bold;'>PT. Wahana Indo Trada</h1>
                <p style='margin: 5px 0 0 0; font-size: 14px; opacity: 0.9;'>Pemberitahuan Status Servis Kendaraan</p>
            </div>
            
            <!-- Main Content Card -->
            <div style='background-color: #ffffff; padding: 25px; border-radius: 0 0 8px 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);'>
                <p style='font-size: 16px; color: #334155; margin-top: 0;'>Halo <strong>" . htmlspecialchars($cust_name) . "</strong>,</p>
                <p style='font-size: 15px; color: #475569; line-height: 1.5;'>$intro_text</p>
                
                <!-- Rincian Utama -->
                <div style='background-color: #f1f5f9; padding: 15px; border-radius: 6px; margin: 20px 0;'>
                    <table width='100%' cellpadding='0' cellspacing='0' style='font-size: 14px;'>
                        <tr><td width='35%' style='padding: 6px 0; color: #64748b;'>Kode Booking</td><td style='padding: 6px 0; font-weight: bold; color: #0f172a;'>: $bk_code</td></tr>
                        <tr><td style='padding: 6px 0; color: #64748b;'>Layanan Servis</td><td style='padding: 6px 0; font-weight: bold; color: #2563eb;'>: " . htmlspecialchars($service_name) . "</td></tr>
                        <tr><td style='padding: 6px 0; color: #64748b;'>Jadwal Terpilih</td><td style='padding: 6px 0; font-weight: bold; color: #0f172a;'>: " . date('d M Y', strtotime($tgl_booking)) . ", " . substr($jam_booking, 0, 5) . " WIB</td></tr>
                        <tr><td style='padding: 6px 0; color: #64748b;'>Status Pemesanan</td><td style='padding: 6px 0; font-weight: bold; color: $status_color;'>: " . strtoupper($status_label) . "</td></tr>
                    </table>
                </div>

                <!-- Data Kendaraan -->
                <h3 style='font-size: 15px; color: #0f172a; margin-bottom: 10px; border-bottom: 2px solid #e2e8f0; padding-bottom: 5px;'>Kendaraan Anda</h3>
                <table width='100%' cellpadding='0' cellspacing='0' style='font-size: 14px; margin-bottom: 20px; border-collapse: collapse;'>
                    <thead>
                        <tr style='background-color: #f8fafc; color: #475569; text-align: left;'>
                            <th style='padding: 8px; border-bottom: 2px solid #cbd5e1;'>Nama/Merek Kendaraan</th>
                            <th style='padding: 8px; border-bottom: 2px solid #cbd5e1;'>Nomor Polisi</th>
                        </tr>
                    </thead>
                    <tbody>
                        $kendaraan_html
                    </tbody>
                </table>

                <!-- Keluhan -->
                <h3 style='font-size: 15px; color: #0f172a; margin-bottom: 5px;'>Keluhan / Catatan:</h3>
                <div style='background-color: #f8fafc; border-left: 4px solid #cbd5e1; padding: 12px; font-size: 14px; color: #475569; border-radius: 0 4px 4px 0; margin-bottom: 25px;'>
                    " . nl2br(htmlspecialchars(empty($keluhan) ? '-' : $keluhan)) . "
                </div>

                <!-- Jejak Waktu & Status -->
                <h3 style='font-size: 15px; color: #0f172a; margin-bottom: 10px; border-bottom: 2px solid #e2e8f0; padding-bottom: 5px;'>Histori & Jejak Waktu Status</h3>
                <table width='100%' cellpadding='0' cellspacing='0' style='font-size: 13px; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 8px; border: 1px solid #e2e8f0; color: #475569;' width='40%'>✔️ Dibuat (Baru)</td>
                        <td style='padding: 8px; border: 1px solid #e2e8f0; color: #475569;'>$c_time WIB</td>
                    </tr>
                    <tr style='" . ($is_proses ? "background-color: #eff6ff;" : "") . "'>
                        <td style='padding: 8px; border: 1px solid " . ($is_proses ? "#bfdbfe" : "#e2e8f0") . "; " . ($is_proses ? "font-weight:bold; color:#1d4ed8;" : "color:#475569;") . "'>" . ($is_proses ? "🔵 Diproses" : "✔️ Diproses") . "</td>
                        <td style='padding: 8px; border: 1px solid " . ($is_proses ? "#bfdbfe" : "#e2e8f0") . "; " . ($is_proses ? "color:#1d4ed8;" : "color:#475569;") . "'>" . ($is_proses ? "$p_time WIB" : $p_time) . "</td>
                    </tr>
                    $row3_html
                </table>

                <p style='font-size: 13px; color: #94a3b8; text-align: center; margin-top: 30px; border-top: 1px solid #f1f5f9; padding-top: 15px;'>
                    Terima kasih atas kepercayaan Anda kepada PT. Wahana Indo Trada.<br>
                    Jika ada pertanyaan, silakan hubungi kami via WhatsApp atau kunjungi bengkel kami.
                </p>
            </div>
        </div>";
    };

    if ($action === 'set_proses') {
        $pdo->prepare("UPDATE bookings SET status='Proses', proses_at=NOW() WHERE id=?")->execute([$id]);
        // Update lokal variabel agar ter-render presisi di email
        $proses_at = date('Y-m-d H:i:s');
        if ($target_user_id) {
            $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Pesanan Diproses', 'Hore! Pesanan Anda dengan kode $bk_code sedang diproses oleh bengkel.')")->execute([$target_user_id]);
            if (!empty($cust_email)) {
                $email_body = $make_cust_email('Proses', '#2563eb', 'Pesanan pemesanan antrean servis Anda saat ini <strong>sedang diproses</strong> oleh bengkel kami.', '#2563eb');
                send_email_notification($cust_email, "Pesanan $bk_code Diproses", $email_body);
            }
            if (!empty($no_hp)) {
                $wa_text = urlencode("Halo $cust_name, pesanan Anda dengan kode $bk_code sedang diproses oleh bengkel.");
                $wa_link = "https://wa.me/$no_hp?text=$wa_text";
            }
        }
    } elseif ($action === 'set_selesai') {
        $pdo->prepare("UPDATE bookings SET status='Selesai', selesai_at=NOW() WHERE id=?")->execute([$id]);
        $selesai_at = date('Y-m-d H:i:s');
        if ($target_user_id) {
            $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Servis Selesai', 'Pengerjaan servis $bk_code telah selesai dikerjakan! Anda bisa meninggalkan ulasan pada riwayat booking.')")->execute([$target_user_id]);
            if (!empty($cust_email)) {
                $email_body = $make_cust_email('Selesai', '#059669', 'Pengerjaan servis kendaraan Anda telah <strong>selesai dikerjakan</strong>! Anda dapat segera mengambil kendaraan Anda di bengkel.', '#059669');
                send_email_notification($cust_email, "Servis $bk_code Selesai", $email_body);
            }
            if (!empty($no_hp)) {
                $wa_text = urlencode("Halo $cust_name, pengerjaan servis $bk_code telah selesai dikerjakan! Silakan ambil kendaraan Anda.");
                $wa_link = "https://wa.me/$no_hp?text=$wa_text";
            }
        }
    } elseif ($action === 'set_batal') {
        $pdo->prepare("UPDATE bookings SET status='Batal', batal_at=NOW() WHERE id=?")->execute([$id]);
        $batal_at = date('Y-m-d H:i:s');
        if ($target_user_id) {
            $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Pesanan Dibatalkan/Ditolak', 'Mohon maaf, pesanan $bk_code dibatalkan. Hubungi admin lewat WA untuk info lanjut.')")->execute([$target_user_id]);
            if (!empty($cust_email)) {
                $email_body = $make_cust_email('Batal', '#dc2626', 'Mohon maaf, pesanan antrean servis Anda terpaksa kami <strong>batalkan/tolak</strong>. Silakan hubungi admin kami untuk menjadwalkan ulang atau mendapatkan informasi lebih lanjut.', '#dc2626');
                send_email_notification($cust_email, "Pesanan $bk_code Dibatalkan", $email_body);
            }
            if (!empty($no_hp)) {
                $wa_text = urlencode("Halo $cust_name, mohon maaf pesanan Anda dengan kode $bk_code terpaksa kami batalkan/tolak. Silakan hubungi kami untuk informasi lebih lanjut.");
                $wa_link = "https://wa.me/$no_hp?text=$wa_text";
            }
        }
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM bookings WHERE id=?")->execute([$id]);
        if ($target_user_id) {
            $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Pesanan Dihapus', 'Riwayat pesanan $bk_code telah dihapus dari sistem oleh Admin.')")->execute([$target_user_id]);
        }
    }
    
    // Redirect
    if (!empty($wa_link)) {
        echo "<script>window.open('$wa_link', '_blank'); window.location.href='bookings.php';</script>";
        exit;
    } else {
        header("Location: bookings.php");
        exit;
    }
}

// Fetch Semua Bookings
try {
    $stmt = $pdo->query("SELECT * FROM bookings ORDER BY booking_date DESC");
    $allBookings = $stmt->fetchAll();
} catch (PDOException $e) {
    // If testing using new db schema, it's tgl_booking
    try {
        $stmt = $pdo->query("SELECT * FROM bookings ORDER BY tgl_booking DESC, jam_booking DESC");
        $allBookings = $stmt->fetchAll();
    } catch (PDOException $e2) {
        $allBookings = [];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Booking - Wahana Indo Trada</title>
    <link rel="stylesheet" href="../style.css?v=1.2">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .action-links a { margin-right: 0.5rem; font-size: 0.85rem; text-decoration: none; color: var(--primary); font-weight: 600; }
        .action-links a.btn-danger { color: #d32f2f; }
        .action-links a:hover { text-decoration: underline; }
    </style>
</head>
<body class="dashboard-layout">
    
    <!-- Sidebar Sama seperti dashboard -->
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
            <li><a href="bookings.php" class="active"><span class="material-symbols-outlined">calendar_month</span>Bookings</a></li>
            <li><a href="promos.php"><span class="material-symbols-outlined">sell</span>Promotions</a></li>
            <li><a href="customers.php"><span class="material-symbols-outlined">group</span>Customers</a></li>
            <li><a href="reviews.php"><span class="material-symbols-outlined">reviews</span>Reviews</a></li>
        </ul>

        <div class="sidebar-footer">
            <ul class="sidebar-nav" style="gap:0;">
                <li>
                    <a href="profile.php">
                        <span class="material-symbols-outlined">person</span>
                        Profil Admin
                    </a>
                </li>
                <li><a href="javascript:void(0)" onclick="confirmLogout(event, '../auth.php?action=logout');"><span class="material-symbols-outlined">logout</span>Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="dashboard-main">
        
        <header class="dashboard-topbar">
            <div class="topbar-title">
                <h1>Daftar Pemesanan</h1>
                <p>Manajemen antrean servis pelanggan.</p>
            </div>
            <div class="topbar-actions">
                <button class="material-symbols-outlined" style="position:relative; border:none; background:none; cursor:pointer; color:var(--on-surface-variant); margin-right:1rem; font-size:1.5rem;" onclick="window.location.href='../notifications.php'">
                    notifications
                    <?php if($unread_notifs > 0): ?><span style="position:absolute; top:-2px; right:-2px; background:var(--error); color:white; border-radius:50%; font-size:0.6rem; width:14px; height:14px; display:flex; align-items:center; justify-content:center; font-family:sans-serif; font-weight:bold;"><?= $unread_notifs ?></span><?php endif; ?>
                </button>
                <div class="topbar-profile">
                    <a href="profile.php" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                        <span class="topbar-profile-name">Hi, <?= htmlspecialchars($admin_name) ?></span>
                        <span class="material-symbols-outlined" style="color:var(--primary);">account_circle</span>
                    </a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <section class="table-card">
                <div class="table-header">
                    <h2>Semua Antrean Booking</h2>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Customer</th>
                                <th>Layanan & Detail</th>
                                <th>Tgl & Waktu</th>
                                <th>Status</th>
                                <th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($allBookings)): ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; padding: 2rem;">Belum ada antrean.</td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $colorClasses = ['avatar-1', 'avatar-2', 'avatar-3', 'avatar-default'];
                                foreach($allBookings as $index => $bk): 
                                    $avatarClass = $colorClasses[$index % 4];
                                    $badgeClass = 'badge-baru';
                                    if ($bk['status'] === 'Proses') $badgeClass = 'badge-proses';
                                    if ($bk['status'] === 'Selesai') $badgeClass = 'badge-selesai';
                                    if ($bk['status'] === 'Batal') $badgeClass = 'badge-batal'; // Custom styling optional
                                ?>
                                <tr class="booking-row-item" style="cursor: pointer;" title="Klik untuk melihat detail jejak waktu proses"
                                    data-code="<?= htmlspecialchars($bk['booking_code']) ?>"
                                    data-service="<?= htmlspecialchars($bk['service_name']) ?>"
                                    data-status="<?= htmlspecialchars($bk['status']) ?>"
                                    data-created="<?= isset($bk['created_at']) ? htmlspecialchars($bk['created_at']) : '' ?>"
                                    data-proses="<?= isset($bk['proses_at']) ? htmlspecialchars($bk['proses_at']) : '' ?>"
                                    data-selesai="<?= isset($bk['selesai_at']) ? htmlspecialchars($bk['selesai_at']) : '' ?>"
                                    data-batal="<?= isset($bk['batal_at']) ? htmlspecialchars($bk['batal_at']) : '' ?>"
                                    onclick="showTimelineModal(this, event)">
                                    <td style="font-weight: 500;"><?= htmlspecialchars($bk['booking_code']) ?></td>
                                    <td>
                                        <div class="table-customer">
                                            <div class="customer-avatar <?= $avatarClass ?>">
                                                <?= htmlspecialchars($bk['customer_initials']) ?>
                                            </div>
                                            <span><?= htmlspecialchars($bk['customer_name']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($bk['service_name']) ?></strong>
                                        <div style="font-size: 0.8rem; color: var(--on-surface-variant); margin-top: 0.5rem; background: var(--surface-variant); padding: 0.5rem; border-radius: 0.3rem;">
                                            <?php 
                                            $kendaraan = json_decode($bk['kendaraan_details'], true);
                                            if (is_array($kendaraan) && !empty($kendaraan)) {
                                                echo "<strong>Kendaraan:</strong><br>";
                                                foreach($kendaraan as $k) {
                                                    echo "- " . htmlspecialchars($k['nama']) . " (" . htmlspecialchars($k['plat']) . ")<br>";
                                                }
                                            }
                                            ?>
                                            <div style="margin-top: 0.3rem;">
                                                <strong>Keluhan/Catatan:</strong><br>
                                                <?= nl2br(htmlspecialchars($bk['keluhan'])) ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="color: var(--on-surface-variant);">
                                        <?php if(isset($bk['tgl_booking'])): ?>
                                            <?= date('d M Y', strtotime($bk['tgl_booking'])) ?>, <?= substr($bk['jam_booking'],0,5) ?>
                                        <?php else: ?>
                                            <?= date('d M Y, H:i', strtotime($bk['booking_date'])) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $badgeClass ?>">
                                            <?= htmlspecialchars($bk['status']) ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;" class="action-links">
                                        <?php if($bk['status'] === 'Baru'): ?>
                                            <a href="?action=set_proses&id=<?= $bk['id'] ?>">Proses</a> |
                                            <a href="?action=set_batal&id=<?= $bk['id'] ?>" class="btn-danger">Tolak</a> |
                                        <?php elseif($bk['status'] === 'Proses'): ?>
                                            <a href="?action=set_selesai&id=<?= $bk['id'] ?>">Selesai</a> |
                                        <?php endif; ?>
                                        <a href="?action=delete&id=<?= $bk['id'] ?>" onclick="return confirm('Hapus permanen booking ini?')" class="btn-danger">Hapus</a>
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
function showTimelineModal(rowEl, event) {
    // Abaikan jika klik terjadi pada link aksi seperti Proses, Tolak, Selesai, Hapus
    if (event && event.target && ['a', 'button', 'input'].includes(event.target.tagName.toLowerCase())) {
        return;
    }

    const code = rowEl.getAttribute('data-code') || '-';
    const service = rowEl.getAttribute('data-service') || '-';
    const status = rowEl.getAttribute('data-status') || 'Baru';
    
    const created = rowEl.getAttribute('data-created') || '';
    const proses = rowEl.getAttribute('data-proses') || '';
    const selesai = rowEl.getAttribute('data-selesai') || '';
    const batal = rowEl.getAttribute('data-batal') || '';

    const fmt = (str) => {
        if(!str || str.trim() === '') return '<span style="color:#aaa; font-size:0.7rem; font-style:italic;">Menunggu</span>';
        const parts = str.split(' ');
        const tgl = parts[0] ? parts[0].split('-') : [];
        const jam = parts[1] ? parts[1].substring(0,5) : '';
        if (tgl.length === 3) {
            const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            const mIdx = parseInt(tgl[1], 10) - 1;
            return `${tgl[2]} ${months[mIdx] || tgl[1]} ${tgl[0]}<br><strong style="color:var(--primary); font-size:0.85rem;">${jam}</strong>`;
        }
        return str;
    };

    let step1Bg = '#25D366'; 

    let step2Class = ['Proses', 'Selesai'].includes(status) ? 'active' : 'inactive';
    let step2Bg = status === 'Selesai' ? '#25D366' : (status === 'Proses' ? 'var(--primary)' : '#f1f5f9');
    let step2Color = ['Proses', 'Selesai'].includes(status) ? '#fff' : '#64748b';

    let step3Class = ['Selesai', 'Batal'].includes(status) ? 'active' : 'inactive';
    let step3Bg = status === 'Selesai' ? '#25D366' : (status === 'Batal' ? '#ef4444' : '#f1f5f9');
    let step3Color = ['Selesai', 'Batal'].includes(status) ? '#fff' : '#64748b';
    let step3Label = status === 'Batal' ? 'Ditolak/Batal' : 'Selesai';
    let step3Time = status === 'Batal' ? batal : selesai;

    const timelineHtml = `
        <div style="text-align: left; margin-bottom: 1.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.8rem;">
            <p style="margin: 0; font-size: 0.95rem; color: var(--on-surface-variant);">Layanan: <strong style="color:var(--on-surface);">${service}</strong></p>
            <p style="margin: 0.3rem 0 0 0; font-size: 0.95rem; color: var(--on-surface-variant);">Status: <span style="background:var(--primary-container); color:var(--on-primary-container); padding:0.2rem 0.6rem; border-radius:1rem; font-weight:bold; font-size:0.85rem;">${status}</span></p>
        </div>
        
        <!-- Alur Waktu Horizontal Mirip Progress Indicator Foto -->
        <div style="display: flex; align-items: flex-start; justify-content: space-between; position: relative; margin: 2.5rem 0 1.5rem 0; padding: 0 0.5rem;">
            <!-- Garis Latar -->
            <div style="position: absolute; top: 20px; left: 15%; right: 15%; height: 3px; background: #cbd5e1; z-index: 1;"></div>
            
            <!-- Garis Terisi -->
            <div style="position: absolute; top: 20px; left: 15%; width: ${status==='Selesai' ? '70%' : (status==='Proses' ? '35%' : '0%')}; height: 3px; background: #25D366; z-index: 2; transition: width 0.5s ease;"></div>

            <!-- Step 1: Dibuat -->
            <div style="position: relative; z-index: 3; display: flex; flex-direction: column; align-items: center; width: 32%; background: #fff;">
                <div style="width: 42px; height: 42px; border-radius: 50%; background: ${step1Bg}; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.1rem; box-shadow: 0 4px 10px rgba(37,211,102,0.3);">1</div>
                <span style="font-size: 0.85rem; font-weight: 700; margin-top: 0.6rem; color: var(--on-surface);">Dibuat</span>
                <div style="font-size: 0.75rem; color: var(--on-surface-variant); text-align: center; margin-top: 0.3rem; line-height: 1.3;">
                    ${fmt(created)}
                </div>
            </div>

            <!-- Step 2: Proses -->
            <div style="position: relative; z-index: 3; display: flex; flex-direction: column; align-items: center; width: 32%; background: #fff;">
                <div style="width: 42px; height: 42px; border-radius: 50%; background: ${step2Bg}; color: ${step2Color}; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.1rem; border: ${step2Class==='inactive' ? '1px solid #cbd5e1' : 'none'}; box-shadow: ${step2Class==='active' ? '0 4px 10px rgba(0,0,0,0.15)' : 'none'};">2</div>
                <span style="font-size: 0.85rem; font-weight: ${step2Class==='active' ? '700' : '500'}; margin-top: 0.6rem; color: ${step2Class==='active' ? 'var(--on-surface)' : 'var(--on-surface-variant)'};">Diproses</span>
                <div style="font-size: 0.75rem; color: var(--on-surface-variant); text-align: center; margin-top: 0.3rem; line-height: 1.3;">
                    ${fmt(proses)}
                </div>
            </div>

            <!-- Step 3: Selesai / Batal -->
            <div style="position: relative; z-index: 3; display: flex; flex-direction: column; align-items: center; width: 32%; background: #fff;">
                <div style="width: 42px; height: 42px; border-radius: 50%; background: ${step3Bg}; color: ${step3Color}; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.1rem; border: ${step3Class==='inactive' ? '1px solid #cbd5e1' : 'none'}; box-shadow: ${step3Class==='active' ? '0 4px 10px rgba(0,0,0,0.15)' : 'none'};">3</div>
                <span style="font-size: 0.85rem; font-weight: ${step3Class==='active' ? '700' : '500'}; margin-top: 0.6rem; color: ${status==='Batal' ? '#ef4444' : (step3Class==='active' ? 'var(--on-surface)' : 'var(--on-surface-variant)')};">${step3Label}</span>
                <div style="font-size: 0.75rem; color: var(--on-surface-variant); text-align: center; margin-top: 0.3rem; line-height: 1.3;">
                    ${fmt(step3Time)}
                </div>
            </div>
        </div>
    `;

    Swal.fire({
        title: `Jejak Waktu: ${code}`,
        html: timelineHtml,
        width: 550,
        confirmButtonText: 'Tutup Detail',
        confirmButtonColor: 'var(--primary)',
        backdrop: `rgba(0,0,0,0.4)`
    });
}

function confirmLogout(event, url) {
    event.preventDefault();
    Swal.fire({
        title: 'Konfirmasi Logout',
        text: 'Apakah Anda yakin ingin keluar dari sesi admin?',
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
