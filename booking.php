<?php
session_start();
require_once 'config.php';

// Proteksi Halaman: Hanya yang sudah login dapat akses booking
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu untuk melakukan pemesanan.'); window.location.href='auth.php';</script>";
    exit;
}

$success_msg = '';
$error_msg = '';

// Ambil email admin
$stmt_admin = $pdo->query("SELECT email FROM users WHERE role = 'admin' LIMIT 1");
$admin_data = $stmt_admin->fetch();
$admin_email = $admin_data ? $admin_data['email'] : ADMIN_EMAIL;

// Proses Form Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $customer_name = $_SESSION['fullname'] ?? 'Customer';
    
    // Inisial nama (2 huruf pertama)
    $words = explode(" ", $customer_name);
    $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));

    $service_id = $_POST['category'] ?? ''; // ID kategori dari radio
    $keluhan = $_POST['keluhan'] ?? '';
    $tgl_booking = $_POST['tgl_booking'] ?? '';
    $jam_booking = $_POST['jam_booking'] ?? '';
    $nama_kendaraans = $_POST['nama_kendaraan'] ?? [];
    $plat_kendaraans = $_POST['plat_kendaraan'] ?? [];

    // Gabungkan kendaraan menjadi JSON
    $kendaraan_list = [];
    foreach ($nama_kendaraans as $index => $nama) {
        $plat = $plat_kendaraans[$index] ?? '';
        if (!empty($nama) || !empty($plat)) {
            $kendaraan_list[] = ['nama' => $nama, 'plat' => $plat];
        }
    }
    $kendaraan_details = json_encode($kendaraan_list);

    // Validasi Tanggal (H+2 dan bukan hari Minggu)
    $valid_date = true;
    if (!empty($tgl_booking)) {
        $date_timestamp = strtotime($tgl_booking);
        $min_date = strtotime(date('Y-m-d', strtotime('+2 days')));
        $day_of_week = date('w', $date_timestamp);
        
        if ($date_timestamp < $min_date) {
            $valid_date = false;
            $error_msg = "Tanggal booking minimal H+2 dari hari ini.";
        } elseif ($day_of_week == 0) { // 0 = Sunday
            $valid_date = false;
            $error_msg = "Bengkel tutup pada hari Minggu. Silakan pilih hari lain.";
        } else {
            // Validasi Jam Operasional
            if (!empty($jam_booking)) {
                if ($day_of_week == 6) { // Saturday
                    if ($jam_booking < "08:30" || $jam_booking > "15:00") {
                        $valid_date = false;
                        $error_msg = "Jam operasional hari Sabtu adalah 08:30 - 15:00.";
                    }
                } else { // Mon - Fri
                    if ($jam_booking < "08:30" || $jam_booking > "16:30") {
                        $valid_date = false;
                        $error_msg = "Jam operasional Senin - Jumat adalah 08:30 - 16:30.";
                    }
                }
            }
        }
    }

    // Generate Booking Code: B- + user_id + timestamp
    $booking_code = 'B-' . rand(1000, 9999);

    if ($valid_date && !empty($service_id) && !empty($tgl_booking) && !empty($jam_booking)) {
        // Cek nama service untuk tabel booking
        $stmt_svc = $pdo->prepare("SELECT title FROM services WHERE id = ?");
        $stmt_svc->execute([$service_id]);
        $svc_data = $stmt_svc->fetch();
        $service_name = $svc_data ? $svc_data['title'] : 'Layanan Default';

        try {
            $stmt = $pdo->prepare("INSERT INTO bookings (booking_code, user_id, customer_name, customer_initials, service_id, service_name, tgl_booking, jam_booking, kendaraan_details, keluhan, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Baru')");
            $stmt->execute([$booking_code, $user_id, $customer_name, $initials, $service_id, $service_name, $tgl_booking, $jam_booking, $kendaraan_details, $keluhan]);
            
            // Tembak Notifikasi ke Admin (in-app)
            $notif_title = "Pesanan Baru {$booking_code}";
            $notif_msg = "Pelanggan {$customer_name} baru saja memesan layanan {$service_name} untuk tanggal {$tgl_booking}.";
            $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (NULL, ?, ?)")->execute([$notif_title, $notif_msg]);

            // Kirim Email ke Admin
            $kendaraan_html = "";
            foreach($kendaraan_list as $k) {
                $kendaraan_html .= "<tr><td style='padding:8px; border-bottom:1px solid #eee;'><strong>" . htmlspecialchars($k['nama']) . "</strong></td><td style='padding:8px; border-bottom:1px solid #eee;'>" . htmlspecialchars($k['plat']) . "</td></tr>";
            }
            if(empty($kendaraan_html)) {
                $kendaraan_html = "<tr><td colspan='2' style='padding:8px; color:#999;'>Tidak ada data kendaraan</td></tr>";
            }
            
            $now_str = date('d M Y, H:i');
            
            $email_subject = "Booking Servis Baru - $booking_code";
            $email_body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #f8fafc; padding: 20px; border-radius: 8px;'>
                <!-- Header Banner -->
                <div style='background-color: #1e293b; color: #ffffff; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                    <h1 style='margin: 0; font-size: 22px; font-weight: bold;'>PT. Wahana Indo Trada</h1>
                    <p style='margin: 5px 0 0 0; font-size: 14px; color: #94a3b8;'>Pemberitahuan Pemesanan Servis Baru</p>
                </div>
                
                <!-- Main Content Card -->
                <div style='background-color: #ffffff; padding: 25px; border-radius: 0 0 8px 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);'>
                    <p style='font-size: 16px; color: #334155; margin-top: 0;'>Halo <strong>Tim Admin</strong>,</p>
                    <p style='font-size: 15px; color: #475569; line-height: 1.5;'>Terdapat pemesanan antrean servis baru yang diajukan oleh pelanggan melalui sistem. Berikut adalah rincian pemesanan:</p>
                    
                    <!-- Rincian Utama -->
                    <div style='background-color: #f1f5f9; padding: 15px; border-radius: 6px; margin: 20px 0;'>
                        <table width='100%' cellpadding='0' cellspacing='0' style='font-size: 14px;'>
                            <tr><td width='35%' style='padding: 6px 0; color: #64748b;'>Kode Booking</td><td style='padding: 6px 0; font-weight: bold; color: #0f172a;'>: $booking_code</td></tr>
                            <tr><td style='padding: 6px 0; color: #64748b;'>Nama Customer</td><td style='padding: 6px 0; font-weight: bold; color: #0f172a;'>: " . htmlspecialchars($customer_name) . "</td></tr>
                            <tr><td style='padding: 6px 0; color: #64748b;'>Layanan Servis</td><td style='padding: 6px 0; font-weight: bold; color: #2563eb;'>: " . htmlspecialchars($service_name) . "</td></tr>
                            <tr><td style='padding: 6px 0; color: #64748b;'>Jadwal Servis</td><td style='padding: 6px 0; font-weight: bold; color: #0f172a;'>: " . date('d M Y', strtotime($tgl_booking)) . ", " . substr($jam_booking, 0, 5) . " WIB</td></tr>
                        </table>
                    </div>

                    <!-- Data Kendaraan -->
                    <h3 style='font-size: 15px; color: #0f172a; margin-bottom: 10px; border-bottom: 2px solid #e2e8f0; padding-bottom: 5px;'>Data Kendaraan</h3>
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
                    <h3 style='font-size: 15px; color: #0f172a; margin-bottom: 5px;'>Keluhan / Catatan Pelanggan:</h3>
                    <div style='background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 12px; font-size: 14px; color: #78350f; border-radius: 0 4px 4px 0; margin-bottom: 25px;'>
                        " . nl2br(htmlspecialchars(empty($keluhan) ? 'Tidak ada catatan khusus' : $keluhan)) . "
                    </div>

                    <!-- Jejak Waktu & Status -->
                    <h3 style='font-size: 15px; color: #0f172a; margin-bottom: 10px; border-bottom: 2px solid #e2e8f0; padding-bottom: 5px;'>Jejak Waktu & Status Proses</h3>
                    <table width='100%' cellpadding='0' cellspacing='0' style='font-size: 13px; border-collapse: collapse;'>
                        <tr style='background-color: #ecfdf5;'>
                            <td style='padding: 8px; border: 1px solid #a7f3d0; font-weight: bold; color: #065f46;' width='40%'>🟢 Dibuat (Status: Baru)</td>
                            <td style='padding: 8px; border: 1px solid #a7f3d0; color: #065f46;'>$now_str WIB</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #e2e8f0; color: #94a3b8;'>🔵 Diproses</td>
                            <td style='padding: 8px; border: 1px solid #e2e8f0; color: #94a3b8; font-style: italic;'>Menunggu konfirmasi admin</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #e2e8f0; color: #94a3b8;'>⚪ Selesai / Ditolak</td>
                            <td style='padding: 8px; border: 1px solid #e2e8f0; color: #94a3b8; font-style: italic;'>Menunggu pengerjaan</td>
                        </tr>
                    </table>

                    <p style='font-size: 13px; color: #94a3b8; text-align: center; margin-top: 30px; border-top: 1px solid #f1f5f9; padding-top: 15px;'>
                        Email ini di-generate otomatis oleh Sistem Layanan Bengkel PT. Wahana Indo Trada.<br>
                        Mohon segera periksa dashboard admin untuk memproses pesanan ini.
                    </p>
                </div>
            </div>";
            send_email_notification($admin_email, $email_subject, $email_body);

            // Kirim Email ke Customer
            $stmt_u = $pdo->prepare("SELECT email FROM users WHERE id=?");
            $stmt_u->execute([$user_id]);
            $uData = $stmt_u->fetch();
            $cust_email = $uData['email'] ?? '';
            
            if (!empty($cust_email)) {
                $cust_subject = "Pemesanan Servis Berhasil - $booking_code";
                $cust_body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #f8fafc; padding: 20px; border-radius: 8px;'>
                    <div style='background-color: #0f172a; color: #ffffff; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                        <h1 style='margin: 0; font-size: 22px; font-weight: bold;'>PT. Wahana Indo Trada</h1>
                        <p style='margin: 5px 0 0 0; font-size: 14px; opacity: 0.9;'>Pemberitahuan Status Servis Kendaraan</p>
                    </div>
                    <div style='background-color: #ffffff; padding: 25px; border-radius: 0 0 8px 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);'>
                        <p style='font-size: 16px; color: #334155; margin-top: 0;'>Halo <strong>" . htmlspecialchars($customer_name) . "</strong>,</p>
                        <p style='font-size: 15px; color: #475569; line-height: 1.5;'>Terima kasih telah melakukan pemesanan antrean servis. Pesanan Anda saat ini <strong>Berhasil Dibuat</strong> dan sedang menunggu proses konfirmasi oleh bengkel kami.</p>
                        
                        <div style='background-color: #f1f5f9; padding: 15px; border-radius: 6px; margin: 20px 0;'>
                            <table width='100%' cellpadding='0' cellspacing='0' style='font-size: 14px;'>
                                <tr><td width='35%' style='padding: 6px 0; color: #64748b;'>Kode Booking</td><td style='padding: 6px 0; font-weight: bold; color: #0f172a;'>: $booking_code</td></tr>
                                <tr><td style='padding: 6px 0; color: #64748b;'>Layanan Servis</td><td style='padding: 6px 0; font-weight: bold; color: #2563eb;'>: " . htmlspecialchars($service_name) . "</td></tr>
                                <tr><td style='padding: 6px 0; color: #64748b;'>Jadwal Terpilih</td><td style='padding: 6px 0; font-weight: bold; color: #0f172a;'>: " . date('d M Y', strtotime($tgl_booking)) . ", " . substr($jam_booking, 0, 5) . " WIB</td></tr>
                                <tr><td style='padding: 6px 0; color: #64748b;'>Status Pemesanan</td><td style='padding: 6px 0; font-weight: bold; color: #0f172a;'>: BARU</td></tr>
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

                        <!-- Jejak Waktu & Status -->
                        <h3 style='font-size: 15px; color: #0f172a; margin-bottom: 10px; border-bottom: 2px solid #e2e8f0; padding-bottom: 5px;'>Histori & Jejak Waktu Status</h3>
                        <table width='100%' cellpadding='0' cellspacing='0' style='font-size: 13px; border-collapse: collapse;'>
                            <tr style='background-color: #f8fafc;'>
                                <td style='padding: 8px; border: 1px solid #e2e8f0; font-weight: bold; color: #475569;' width='40%'>✔️ Dibuat (Baru)</td>
                                <td style='padding: 8px; border: 1px solid #e2e8f0; color: #475569;'>$now_str WIB</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px; border: 1px solid #e2e8f0; color: #94a3b8;'>🔵 Diproses</td>
                                <td style='padding: 8px; border: 1px solid #e2e8f0; color: #94a3b8; font-style: italic;'>Menunggu</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px; border: 1px solid #e2e8f0; color: #94a3b8;'>⚪ Selesai</td>
                                <td style='padding: 8px; border: 1px solid #e2e8f0; color: #94a3b8; font-style: italic;'>Menunggu</td>
                            </tr>
                        </table>

                        <p style='font-size: 13px; color: #94a3b8; text-align: center; margin-top: 30px; border-top: 1px solid #f1f5f9; padding-top: 15px;'>
                            Terima kasih atas kepercayaan Anda kepada PT. Wahana Indo Trada.<br>
                            Anda akan menerima pemberitahuan lebih lanjut saat pesanan Anda diproses.
                        </p>
                    </div>
                </div>";
                send_email_notification($cust_email, $cust_subject, $cust_body);
            }

            $success_msg = "Booking berhasil diajukan dengan kode: $booking_code. Silakan konfirmasi ke Admin kami melalui WhatsApp.";
        } catch (PDOException $e) {
            $error_msg = "Gagal memproses booking. Silakan coba lagi. Error: " . $e->getMessage();
        }
    } elseif(empty($error_msg)) {
        $error_msg = "Harap lengkapi semua kolom yang wajib (Kategori, Tanggal, dan Jam).";
    }
}

// Fetch categories from services table
$categories = [];
try {
    $stmt = $pdo->query("SELECT * FROM services ORDER BY id ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    // Ignore error if table not created
}

// Fetch upcoming bookings untuk memunculkan slot yang sudah terisi
$upcoming_bookings = [];
try {
    $stmt = $pdo->query("SELECT tgl_booking, jam_booking FROM bookings WHERE tgl_booking >= CURDATE() AND status != 'Batal' ORDER BY tgl_booking ASC, jam_booking ASC LIMIT 15");
    $upcoming_bookings = $stmt->fetchAll();
} catch (PDOException $e) { /* ignore */ }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking - Wahana Indo Trada</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body style="display: flex; flex-direction: column; min-height: 100vh;">

    <!-- Top Navigation Bar (Same as index.php) -->
    <nav class="navbar">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <div class="nav-brand" style="cursor: pointer;" onclick="window.location.href='index.php'">
                <img src="logo.png" alt="PT. Wahana Indo Trada" style="height: 35px; width: auto;" onerror="this.onerror=null; this.src=''; this.alt='PT. Wahana Indo Trada'; this.style.fontSize='1.2rem'; this.style.fontWeight='900'; this.style.color='var(--primary)';">
            </div>
            
            <div class="nav-links">
                <a href="index.php">Services</a>
                <a href="booking.php" class="active">My Bookings</a>
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

    <!-- Main Content -->
    <main class="main-wrapper">

        <!-- Page Header -->
        <div style="margin-bottom: 3rem;">
            <h1 class="page-title">Pilih Servis</h1>
            <p class="page-subtitle">Lengkapi detail kebutuhan servis kendaraan Anda.</p>

            <!-- Catatan Aturan Booking -->
            <div style="background: rgba(255,179,177,0.15); border-left: 4px solid var(--secondary); padding: 0.8rem 1rem; border-radius: 0 0.4rem 0.4rem 0; margin-top: 1.5rem;">
                <span style="font-weight: 600; color: var(--secondary); display: flex; align-items: center; gap: 0.4rem; font-size: 0.9rem;">
                    <span class="material-symbols-outlined" style="font-size: 1.1rem;">info</span>
                    Catatan Penting:
                </span>
                <p style="font-size: 0.85rem; color: var(--on-surface-variant); margin-top: 0.2rem;">
                    1 akun hanya dapat melakukan booking untuk 1 waktu/slot saja. Jika ingin melakukan pemesanan ganda di waktu yang sama atau mengubah jadwal, silakan hubungi WhatsApp Admin.
                </p>
            </div>
        </div>

        <!-- Progress Indicator -->
        <div class="progress-container">
            <div class="progress-line"></div>
            
            <!-- Step 1 -->
            <div class="progress-step">
                <div class="step-circle active" id="step-circle-1" style="color: #ffffff; transition: all 0.4s ease;">1</div>
                <span class="step-label" id="step-label-1">Pilih Servis</span>
            </div>
            
            <!-- Step 2 -->
            <div class="progress-step">
                <div class="step-circle inactive" id="step-circle-2" style="transition: all 0.4s ease;">2</div>
                <span class="step-label" id="step-label-2">Jadwal</span>
            </div>

            <!-- Step 3 -->
            <div class="progress-step">
                <div class="step-circle inactive" id="step-circle-3" style="transition: all 0.4s ease;">3</div>
                <span class="step-label" id="step-label-3">Konfirmasi</span>
            </div>
        </div>

        <?php if($error_msg): ?>
            <div style="background: var(--error-container); color: var(--on-error-container); padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem; font-weight: 500;">
                <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>
        
        <?php if($success_msg): ?>
            <div style="background: #e2fce6; color: #0d4a1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem; font-weight: 500;">
                <?= htmlspecialchars($success_msg) ?>
                <br><br>
                <?php
                    $wa_text = urlencode("Halo Admin, saya baru saja melakukan booking servis dengan kode $booking_code. Mohon diproses ya.");
                ?>
                <a href="https://wa.me/<?= WA_NUMBER ?>?text=<?= $wa_text ?>" target="_blank" class="btn-primary" style="text-decoration:none; display:inline-block; margin-top:0.5rem; background-color: #25D366; border-color: #25D366; box-shadow: none;">Chat WA Admin Sekarang</a>
                <a href="index.php" class="btn-secondary" style="text-decoration:none; display:inline-block; margin-top:0.5rem; margin-left: 0.5rem;">Kembali ke Beranda</a>
            </div>
        <?php else: ?>

        <form action="booking.php" method="POST" class="booking-form">

            <!-- Category Selection -->
            <section>
                <h2 class="section-title">Kategori Layanan</h2>
                <div class="radio-grid">
                    <?php if (empty($categories)): ?>
                        <p>Belum ada kategori layanan yang tersedia.</p>
                    <?php else: ?>
                        <?php foreach($categories as $index => $cat): ?>
                        <label class="radio-card-label">
                            <input type="radio" name="category" value="<?= htmlspecialchars($cat['id']) ?>" class="radio-card-input" <?= $index === 0 ? 'checked' : '' ?>>
                            <div class="radio-card-content">
                                <div class="radio-icon-wrap">
                                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">
                                        <?= htmlspecialchars($cat['icon']) ?>
                                    </span>
                                </div>
                                <div class="radio-text">
                                    <h3><?= htmlspecialchars($cat['title']) ?></h3>
                                    <!-- Extract first 2 features as description representation -->
                                    <?php 
                                        $feats = json_decode($cat['features'], true); 
                                        $desc = is_array($feats) ? implode(", ", array_slice($feats, 0, 2)) . "..." : "";
                                    ?>
                                    <p><?= htmlspecialchars($desc) ?></p>
                                </div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Service Details -->
            <section class="form-section">
                <h2 class="section-title" style="margin-bottom: 0.5rem;">Detail Servis</h2>
                

                <!-- Panel Informasi Slot Waktu Terisi -->
                <div style="background: var(--surface-variant); padding: 1rem; border-radius: 0.5rem; margin-top: 1rem;">
                    <h3 style="font-family: var(--font-headline); font-size: 1rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span class="material-symbols-outlined" style="color:var(--primary);">calendar_clock</span>
                        Daftar Antrean Berjalan (Tidak Kosong)
                    </h3>
                    <p style="font-size: 0.85rem; color: var(--on-surface-variant); margin-bottom: 0.5rem;">Hindari jam dan tanggal berikut agar tak menumpuk:</p>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <?php if(empty($upcoming_bookings)): ?>
                            <span style="font-size: 0.85rem; padding: 0.3rem 0.6rem; background: var(--surface); border-radius: 1rem; align-self: flex-start;">Belum ada antrean, semua slot bebas!</span>
                        <?php else: ?>
                            <?php foreach($upcoming_bookings as $ub): ?>
                                <span style="font-size: 0.85rem; padding: 0.3rem 0.6rem; background: var(--surface); color: var(--on-surface); border: 1px solid var(--outline); border-radius: 0.5rem; font-weight: 500; align-self: flex-start;">
                                    <?= date('d M Y', strtotime($ub['tgl_booking'])) ?> - <?= substr($ub['jam_booking'],0,5) ?>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Input Kendaraan Dinamis -->
                <div style="margin-top: 1.5rem;" id="kendaraan-container">
                    <h3 class="section-title" style="font-size: 1rem; margin-bottom: 0.5rem;">Data Kendaraan</h3>
                    <div class="kendaraan-row" style="display: flex; gap: 1rem; margin-bottom: 1rem; align-items: flex-end;">
                        <div style="flex: 2;">
                            <label>Nama Kendaraan (Merek/Tipe) <span style="color:red;">*</span></label>
                            <input type="text" name="nama_kendaraan[]" class="form-control" placeholder="Contoh: Toyota Avanza" required>
                        </div>
                        <div style="flex: 1;">
                            <label>Nomor Polisi <span style="color:red;">*</span></label>
                            <input type="text" name="plat_kendaraan[]" class="form-control" placeholder="B 1234 CD" required>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;" onclick="addKendaraanRow()">+ Tambah Kendaraan</button>

                <?php
                    // Hitung min date H+2
                    $min_date = date('Y-m-d', strtotime('+2 days'));
                    // Jika H+2 adalah hari minggu (0), maka H+3
                    if (date('w', strtotime($min_date)) == 0) {
                        $min_date = date('Y-m-d', strtotime('+3 days'));
                    }
                ?>
                <div class="form-group" style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <div style="flex: 1;">
                        <label for="tgl_booking">Tanggal Booking <span style="color:red;">*</span></label>
                        <input type="date" name="tgl_booking" id="tgl_booking" class="form-control" required min="<?= $min_date ?>" onchange="validateDate(this)">
                    </div>
                    <div style="flex: 1;">
                        <label for="jam_booking">Jam Booking <span style="color:red;">*</span></label>
                        <input type="time" name="jam_booking" id="jam_booking" class="form-control" required min="08:30" max="16:30" onchange="validateTime(this)">
                        <small style="color: var(--on-surface-variant); font-size: 0.75rem; display:block; margin-top: 0.3rem;">Sen-Jum (08:30 - 16:30) | Sabtu (08:30 - 15:00)</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="keluhan">Keluhan / Catatan</label>
                    <textarea name="keluhan" id="keluhan" rows="4" class="form-control" placeholder="Jelaskan keluhan pada kendaraan Anda secara detail..."></textarea>
                </div>


            </section>

            <!-- Action Buttons -->
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="window.location.href='index.php'">Batal</button>
                <button type="submit" class="btn-submit">Kirim Booking</button>
            </div>

        </form>
        <?php endif; ?>

    </main>

    <script>
        function addKendaraanRow() {
            const container = document.getElementById('kendaraan-container');
            const row = document.createElement('div');
            row.className = 'kendaraan-row';
            row.style.cssText = 'display: flex; gap: 1rem; margin-bottom: 1rem; align-items: flex-end;';
            row.innerHTML = `
                <div style="flex: 2;">
                    <label>Nama Kendaraan (Merek/Tipe)</label>
                    <input type="text" name="nama_kendaraan[]" class="form-control" placeholder="Contoh: Honda Brio" required>
                </div>
                <div style="flex: 1;">
                    <label>Nomor Polisi</label>
                    <input type="text" name="plat_kendaraan[]" class="form-control" placeholder="D 5678 EF" required>
                </div>
                <button type="button" class="btn-secondary" style="padding: 0.65rem; color: #b91c1c; background: #fee2e2;" onclick="this.parentElement.remove()">
                    <span class="material-symbols-outlined">delete</span>
                </button>
            `;
            container.appendChild(row);
        }

        function validateDate(input) {
            const date = new Date(input.value);
            const timeInput = document.getElementById('jam_booking');
            
            // 0 is Sunday
            if (date.getDay() === 0) {
                Swal.fire({icon: 'warning', title: 'Bengkel Libur', text: 'Pemesanan tidak dapat dilakukan pada hari Minggu karena bengkel libur. Silakan pilih hari lain.', confirmButtonColor: 'var(--primary)'});
                input.value = '';
                timeInput.value = '';
            } else if (date.getDay() === 6) {
                // Saturday
                timeInput.min = "08:30";
                timeInput.max = "15:00";
                if (timeInput.value && (timeInput.value < "08:30" || timeInput.value > "15:00")) {
                    Swal.fire({icon: 'warning', title: 'Jam Operasional', text: 'Jam operasional hari Sabtu adalah 08:30 - 15:00', confirmButtonColor: 'var(--primary)'});
                    timeInput.value = '';
                }
            } else {
                // Mon-Fri
                timeInput.min = "08:30";
                timeInput.max = "16:30";
                if (timeInput.value && (timeInput.value < "08:30" || timeInput.value > "16:30")) {
                    Swal.fire({icon: 'warning', title: 'Jam Operasional', text: 'Jam operasional hari kerja adalah 08:30 - 16:30', confirmButtonColor: 'var(--primary)'});
                    timeInput.value = '';
                }
            }
            updateStepProgress();
        }

        function validateTime(input) {
            const dateInput = document.getElementById('tgl_booking');
            if (!dateInput.value) return;
            
            const date = new Date(dateInput.value);
            if (date.getDay() === 6) {
                if (input.value < "08:30" || input.value > "15:00") {
                    Swal.fire({icon: 'warning', title: 'Jam Operasional', text: 'Jam operasional hari Sabtu adalah 08:30 - 15:00', confirmButtonColor: 'var(--primary)'});
                    input.value = '';
                }
            } else {
                if (input.value < "08:30" || input.value > "16:30") {
                    Swal.fire({icon: 'warning', title: 'Jam Operasional', text: 'Jam operasional hari kerja adalah 08:30 - 16:30', confirmButtonColor: 'var(--primary)'});
                    input.value = '';
                }
            }
            updateStepProgress();
        }

        function updateStepProgress() {
            const catSelected = document.querySelector('input[name="category"]:checked');
            const tglFilled = document.getElementById('tgl_booking') ? document.getElementById('tgl_booking').value : '';
            const jamFilled = document.getElementById('jam_booking') ? document.getElementById('jam_booking').value : '';

            const c1 = document.getElementById('step-circle-1');
            const c2 = document.getElementById('step-circle-2');
            const c3 = document.getElementById('step-circle-3');

            if(!c1) return;

            // Step 1 selalu aktif & warna font putih
            c1.style.color = '#ffffff';
            c1.style.backgroundColor = '#25D366'; // Warna hijau premium menandakan selesai dipilih

            if (catSelected) {
                c2.className = 'step-circle active';
                c2.style.color = '#ffffff';
                if (tglFilled && jamFilled) {
                    c2.style.backgroundColor = '#25D366';
                    c3.className = 'step-circle active';
                    c3.style.color = '#ffffff';
                    c3.style.backgroundColor = 'var(--primary)'; // Siap dikonfirmasi
                } else {
                    c2.style.backgroundColor = 'var(--primary)'; // Sedang aktif mengisi jadwal
                    c3.className = 'step-circle inactive';
                    c3.style.color = '';
                    c3.style.backgroundColor = '';
                }
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('input[name="category"]').forEach(radio => {
                radio.addEventListener('change', updateStepProgress);
            });
            const tglInp = document.getElementById('tgl_booking');
            const jamInp = document.getElementById('jam_booking');
            if(tglInp) tglInp.addEventListener('change', updateStepProgress);
            if(jamInp) jamInp.addEventListener('change', updateStepProgress);
            
            // Setup initial state
            updateStepProgress();
            
            // Intercept form submit untuk memberi animasi konfirmasi step 3 jika form disubmit
            const form = document.querySelector('.booking-form');
            if(form) {
                form.addEventListener('submit', () => {
                    const c3 = document.getElementById('step-circle-3');
                    if(c3) {
                        c3.style.backgroundColor = '#25D366';
                        c3.style.color = '#ffffff';
                    }
                });
            }
        });
    </script>

    <?php if(!empty($error_msg)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Memproses Booking',
                text: <?= json_encode($error_msg) ?>,
                confirmButtonColor: 'var(--secondary)'
            });
        });
    </script>
    <?php endif; ?>

    <?php if(!empty($success_msg)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Ubah semua step menjadi selesai/hijau
            ['step-circle-1', 'step-circle-2', 'step-circle-3'].forEach(id => {
                const el = document.getElementById(id);
                if(el) {
                    el.className = 'step-circle active';
                    el.style.backgroundColor = '#25D366';
                    el.style.color = '#ffffff';
                }
            });

            Swal.fire({
                icon: 'success',
                title: 'Booking Berhasil!',
                html: `<?= addslashes(htmlspecialchars($success_msg)) ?><br><br>
                       <a href="https://wa.me/<?= WA_NUMBER ?>?text=<?= addslashes(isset($wa_text) ? $wa_text : '') ?>" target="_blank" style="background-color: #25D366; color: white; padding: 0.6rem 1.2rem; border-radius: 0.5rem; text-decoration: none; display: inline-block; margin-top: 0.5rem; font-weight: bold; box-shadow: 0 4px 12px rgba(37,211,102,0.3);">💬 Chat WA Admin Sekarang</a>`,
                confirmButtonText: 'Tutup & Lihat Riwayat',
                confirmButtonColor: 'var(--primary)',
                allowOutsideClick: false
            });
        });
    </script>
    <?php endif; ?>
    <!-- Mobile Navigation -->
    <div class="mobile-bottom-nav">
        <a href="index.php" class="mobile-nav-item" style="text-decoration: none;">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">home</span>
            <span>Home</span>
        </a>
        <a href="booking.php" class="mobile-nav-item active" style="text-decoration: none;">
            <span class="material-symbols-outlined">event_note</span>
            <span>Bookings</span>
        </a>
        <a href="katalog_promos.php" class="mobile-nav-item" style="text-decoration: none;">
            <span class="material-symbols-outlined">local_offer</span>
            <span>Promos</span>
        </a>
        <a href="detail.php?kategori=General" class="mobile-nav-item" style="text-decoration: none;">
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
