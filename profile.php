<?php
require_once 'config.php';

// Cek Sesi
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo "<script>alert('Silakan login sebagai Pelanggan untuk melihat profil.'); window.location.href='auth.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil Data Profil
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: auth.php");
    exit;
}

// Inisial avatar
$words = explode(" ", $user['fullname']);
$initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));

// Penanganan Submit Review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $b_id = $_POST['booking_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    
    // Cek apakah sudah direview
    $cek_rev = $pdo->prepare("SELECT id FROM reviews WHERE booking_id = ?");
    $cek_rev->execute([$b_id]);
    if ($cek_rev->fetch()) {
         echo "<script>alert('Anda sudah memberikan ulasan untuk pesanan ini.'); window.location.href='profile.php';</script>";
         exit;
    }

    $stmt_rev = $pdo->prepare("INSERT INTO reviews (booking_id, user_id, customer_name, customer_initials, rating, comment) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt_rev->execute([$b_id, $user_id, $user['fullname'], $initials, $rating, $comment])) {
        // Notif ke Admin
        $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (NULL, 'Ulasan Baru', 'Pelanggan {$user['fullname']} memberikan ulasan baru. Menunggu moderasi.')")->execute();
        echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body><script>Swal.fire({icon: 'success', title: 'Terima kasih!', text: 'Ulasan Anda berhasil dikirim dan akan ditinjau Admin.', showConfirmButton: false, timer: 2000}).then(() => { window.location.href='profile.php'; });</script></body></html>";
    }
    exit;
}

// Ambil Riwayat Booking Pelanggan
$stmt_bk = $pdo->prepare("SELECT b.*, (SELECT id FROM reviews r WHERE r.booking_id = b.id LIMIT 1) as is_reviewed FROM bookings b WHERE b.user_id = ? ORDER BY b.id DESC LIMIT 5");
$stmt_bk->execute([$user_id]);
$my_bookings = $stmt_bk->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Wahana Indo Trada</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .profile-wrapper { max-width: 800px; margin: 4rem auto; padding: 0 1rem; }
        .profile-header { display: flex; align-items: center; gap: 1.5rem; background: var(--surface); padding: 2rem; border-radius: 1rem; box-shadow: var(--shadow-sm); margin-bottom: 2rem; }
        .profile-avatar {
            width: 80px; height: 80px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white; font-size: 2rem; font-weight: bold;
            display: flex; align-items: center; justify-content: center;
        }
        .profile-info h1 { font-family: var(--font-headline); font-size: 1.8rem; margin: 0 0 0.5rem 0; color: var(--on-surface); }
        .profile-info p { color: var(--on-surface-variant); margin: 0 0 0.2rem 0; display: flex; align-items: center; gap: 0.5rem; }
        .btn-logout { background: #fee2e2; color: #b91c1c; border: none; padding: 0.6rem 1.2rem; border-radius: 0.4rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; transition: background 0.3s; }
        .btn-logout:hover { background: #fecaca; }
        
        .history-card { background: var(--surface); padding: 2rem; border-radius: 1rem; box-shadow: var(--shadow-sm); }
        .history-title { font-family: var(--font-headline); font-size: 1.4rem; margin-bottom: 1.5rem; color: var(--primary); }
    </style>
</head>
<body style="background: var(--background);">

    <!-- Top Navigation Bar -->
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
                <a href="profile.php" class="active">Profile</a>
            </div>

            <div class="nav-actions">
                <button class="btn-primary" onclick="window.location.href='booking.php'">Book Now</button>
                <button class="material-symbols-outlined" style="position:relative;" onclick="window.location.href='notifications.php'">
                    notifications
                    <?php if($unread_notifs > 0): ?><span style="position:absolute; top:-2px; right:-2px; background:var(--error); color:white; border-radius:50%; font-size:0.65rem; width:16px; height:16px; display:flex; align-items:center; justify-content:center; font-family:sans-serif; font-weight:bold;"><?= $unread_notifs ?></span><?php endif; ?>
                </button>
                <button class="material-symbols-outlined" onclick="window.location.href='profile.php'" style="color:var(--primary);">account_circle</button>
            </div>
        </div>
    </nav>

    <main class="profile-wrapper">
        <div class="profile-header">
            <div class="profile-avatar"><?= htmlspecialchars($initials) ?></div>
            <div class="profile-info" style="flex: 1;">
                <h1><?= htmlspecialchars($user['fullname']) ?></h1>
                <p><span class="material-symbols-outlined" style="font-size: 1.1rem;">alternate_email</span> <?= htmlspecialchars($user['username']) ?></p>
                <p><span class="material-symbols-outlined" style="font-size: 1.1rem;">mail</span> <?= htmlspecialchars($user['email'] ?? 'Belum ada email') ?></p>
                <p><span class="material-symbols-outlined" style="font-size: 1.1rem;">call</span> <?= htmlspecialchars($user['no_hp'] ?? 'Belum ada nomor') ?></p>
            </div>
            <div>
                <a href="auth.php?action=logout" class="btn-logout" onclick="event.preventDefault(); Swal.fire({title: 'Keluar dari akun?', text: 'Anda harus login kembali untuk memesan servis.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: 'var(--primary)', confirmButtonText: 'Ya, Logout', cancelButtonText: 'Batal'}).then((result) => { if(result.isConfirmed) window.location.href=this.href; });">
                    <span class="material-symbols-outlined">logout</span>
                    Keluar (Logout)
                </a>
            </div>
        </div>

        <div class="history-card">
            <h2 class="history-title">Riwayat Booking Servis Terakhir</h2>
            <?php if(empty($my_bookings)): ?>
                <div style="text-align: center; color: var(--on-surface-variant); padding: 2rem 0;">
                    <span class="material-symbols-outlined" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;">history</span>
                    <p>Anda belum pernah melakukan pemesanan servis.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Layanan & Detail</th>
                                <th>Jadwal Terpilih</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($my_bookings as $bk): 
                                $badgeClass = 'badge-baru';
                                if ($bk['status'] === 'Proses') $badgeClass = 'badge-proses';
                                if ($bk['status'] === 'Selesai') $badgeClass = 'badge-selesai';
                                if ($bk['status'] === 'Batal') $badgeClass = 'badge-batal';
                            ?>
                            <tr class="booking-row-item" style="cursor: pointer;" title="Klik untuk melihat detail & histori waktu proses"
                                data-code="<?= htmlspecialchars($bk['booking_code']) ?>"
                                data-service="<?= htmlspecialchars($bk['service_name']) ?>"
                                data-status="<?= htmlspecialchars($bk['status']) ?>"
                                data-created="<?= isset($bk['created_at']) ? htmlspecialchars($bk['created_at']) : '' ?>"
                                data-proses="<?= isset($bk['proses_at']) ? htmlspecialchars($bk['proses_at']) : '' ?>"
                                data-selesai="<?= isset($bk['selesai_at']) ? htmlspecialchars($bk['selesai_at']) : '' ?>"
                                data-batal="<?= isset($bk['batal_at']) ? htmlspecialchars($bk['batal_at']) : '' ?>"
                                onclick="showTimelineModal(this, event)">
                                <td style="font-weight: bold;"><?= htmlspecialchars($bk['booking_code']) ?></td>
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
                                <td><?= date('d M Y', strtotime($bk['tgl_booking'])) ?>, <?= substr($bk['jam_booking'],0,5) ?></td>
                                <td>
                                    <span class="status-badge <?= $badgeClass ?>"><?= htmlspecialchars($bk['status']) ?></span>
                                    <?php if($bk['status'] === 'Selesai' && !$bk['is_reviewed']): ?>
                                        <div style="margin-top: 0.5rem;">
                                            <button class="btn-primary" style="font-size:0.75rem; padding: 0.3rem 0.6rem;" onclick="document.getElementById('review-form-<?= $bk['id'] ?>').style.display='table-row'">Beri Ulasan</button>
                                        </div>
                                    <?php elseif($bk['is_reviewed']): ?>
                                        <div style="margin-top: 0.5rem; font-size: 0.75rem; color: var(--primary); font-weight:600;"><span class="material-symbols-outlined" style="font-size:1rem; vertical-align:middle;">star</span> Dinilai</div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if($bk['status'] === 'Selesai' && !$bk['is_reviewed']): ?>
                                <tr id="review-form-<?= $bk['id'] ?>" style="display:none; background: var(--surface-variant);">
                                    <td colspan="4" style="padding: 1rem;">
                                        <form method="POST" action="profile.php" style="display:flex; gap: 1rem; align-items: flex-start;">
                                            <input type="hidden" name="booking_id" value="<?= $bk['id'] ?>">
                                            <select name="rating" required style="padding: 0.5rem; border-radius: 0.3rem; border: 1px solid var(--outline);">
                                                <option value="5">⭐⭐⭐⭐⭐ Sangat Baik</option>
                                                <option value="4">⭐⭐⭐⭐ Baik</option>
                                                <option value="3">⭐⭐⭐ Cukup</option>
                                            </select>
                                            <textarea name="comment" required placeholder="Tulis pengalaman mu di sini..." style="flex:1; padding: 0.5rem; border-radius: 0.3rem; border: 1px solid var(--outline); resize:none;"></textarea>
                                            <button type="submit" name="submit_review" class="btn-primary" style="padding: 0.5rem 1rem;">Kirim</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Mobile Navigation -->
    <div class="mobile-bottom-nav">
        <a href="index.php" class="mobile-nav-item" style="text-decoration: none;">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">home</span>
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
        <a href="detail.php" class="mobile-nav-item" style="text-decoration: none;">
            <span class="material-symbols-outlined">info</span>
            <span>Detail</span>
        </a>
        <a href="profile.php" class="mobile-nav-item active" style="text-decoration: none;">
            <span class="material-symbols-outlined">person</span>
            <span>Profile</span>
        </a>
    </div>

    <!-- FAB WhatsApp -->
    <a href="https://wa.me/6285591821790?text=Halo%20Bengkel%20Wahana%20Indo%20Trada.%20Saya%20ingin%20bertanya%20seputar%20servis." target="_blank" class="fab-wa" aria-label="Chat via WhatsApp">
        <svg fill="currentColor" height="28" viewBox="0 0 24 24" width="28">
            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.347-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.876 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"></path>
        </svg>
    </a>

    </a>

<script>
function showTimelineModal(rowEl, event) {
    if (event && event.target && ['button', 'select', 'textarea', 'form', 'option', 'input'].includes(event.target.tagName.toLowerCase())) {
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
        // Format ISO string to simple readable datetime
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
</script>

</body>
</html>
