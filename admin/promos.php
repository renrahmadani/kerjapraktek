<?php
session_start();
require_once '../config.php';

// Proteksi Halaman
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('Akses Ditolak!'); window.location.href='../auth.php';</script>";
    exit;
}

$admin_name = $_SESSION['fullname'] ?? 'Admin';

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $pdo->prepare("DELETE FROM promos WHERE id=?")->execute([$id]);
    header("Location: promos.php");
    exit;
}

// Handle Insert
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $desc = $_POST['description'] ?? '';
    $badge_text = $_POST['badge_text'] ?? '';
    $badge_type = $_POST['badge_type'] ?? 'active';
    $discount_text = $_POST['discount_text'] ?? '';
    $valid_until = $_POST['valid_until'] ?? '';
    
    // Default gradient jika tidak diisi gambar
    $bg_start = $_POST['bg_gradient_start'] ?? 'var(--primary-container)';
    $bg_end = $_POST['bg_gradient_end'] ?? 'var(--primary)';
    
    $image_url_input = $_POST['image_url'] ?? '';
    $final_image_url = '';

    // Prioritas 1: File Upload (Local)
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/promos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true); // Create directory if not exists
        }
        
        $tmp_name = $_FILES['image_file']['tmp_name'];
        $name = time() . '_' . basename($_FILES['image_file']['name']);
        
        // Move uploaded file to uploads directory
        if (move_uploaded_file($tmp_name, $upload_dir . $name)) {
            // Save relative URL format for frontend reading
            $final_image_url = 'uploads/promos/' . $name; 
        }
    } 
    // Prioritas 2: URL Tautan Gambar (External / URL string)
    elseif (!empty($image_url_input)) {
        $final_image_url = $image_url_input;
    }

    if (!empty($title) && !empty($desc)) {
        $stmt = $pdo->prepare("INSERT INTO promos (title, description, badge_text, badge_type, discount_text, bg_gradient_start, bg_gradient_end, image_url, valid_until) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $desc, $badge_text, $badge_type, $discount_text, $bg_start, $bg_end, $final_image_url, $valid_until])) {
            $msg = "<p style='color: green; font-weight: bold;'>Promo berhasil ditambahkan!</p>";
        } else {
            $msg = "<p style='color: red; font-weight: bold;'>Gagal menambahkan promo.</p>";
        }
    } else {
        $msg = "<p style='color: red; font-weight: bold;'>Harap lengkapi Judul dan Deskripsi minimal!</p>";
    }
}

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
    <title>Manajemen Promo - Wahana Indo Trada</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-full { grid-column: span 2; }
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
            <li><a href="promos.php" class="active"><span class="material-symbols-outlined">sell</span>Promotions</a></li>
            <li><a href="customers.php"><span class="material-symbols-outlined">group</span>Customers</a></li>
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
                <h1>Daftar Promosi</h1>
                <p>Kelola etalase promo agresif untuk pengunjung.</p>
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

        <div class="dashboard-content" style="display: grid; gap: 2rem;">
            <?= $msg ?>
            
            <section class="table-card" style="padding: 2rem;">
                <h2 style="margin-bottom: 1rem;">Tambah Promo Baru</h2>
                <form action="promos.php" method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div>
                            <label class="form-label" style="display:block; margin-bottom:0.5rem; font-weight:600;">Judul Promo</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div>
                            <label class="form-label" style="display:block; margin-bottom:0.5rem; font-weight:600;">Valid Sampai (Teks)</label>
                            <input type="text" name="valid_until" class="form-control" placeholder="Contoh: Berlaku s/d 30 Des 2023" required>
                        </div>
                        
                        <div class="form-full">
                            <label class="form-label" style="display:block; margin-bottom:0.5rem; font-weight:600;">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <div>
                            <label class="form-label" style="display:block; margin-bottom:0.5rem; font-weight:600;">Badge Text (Misal: AKTIF / HOT)</label>
                            <input type="text" name="badge_text" class="form-control" required>
                        </div>
                        <div>
                            <label class="form-label" style="display:block; margin-bottom:0.5rem; font-weight:600;">Status Tipe (Active / Soon)</label>
                            <select name="badge_type" class="form-control">
                                <option value="active">Active (Warna Menyala)</option>
                                <option value="soon">Soon (Grayscale Filter)</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="form-label" style="display:block; margin-bottom:0.5rem; font-weight:600;">Teks Diskon Besar (Misal: DISC 50%)</label>
                            <input type="text" name="discount_text" class="form-control" required>
                        </div>
                        
                        <div> <!-- Opsi Gambar --> 
                            <label class="form-label" style="display:block; margin-bottom:0.5rem; font-weight:600;">Upload Gambar Banner (Opsional)</label>
                            <input type="file" name="image_file" class="form-control" accept="image/*" style="padding-top: 0.6rem;">
                            <small>Atau masukkan URL via Tautan di bawah jika tak ingin upload.</small>
                        </div>
                        
                        <div class="form-full">
                            <label class="form-label" style="display:block; margin-bottom:0.5rem; font-weight:600;">Atau Tempel Tautan / URL Gambar Banner (Opsional)</label>
                            <input type="url" name="image_url" class="form-control" placeholder="https://example.com/image.jpg">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary" style="margin-top: 1.5rem; width: 100%;">Terbitkan Promo Agresif</button>
                </form>
            </section>

            <section class="table-card">
                <div class="table-header">
                    <h2>Etalase Terkini (<span style="color:var(--primary);"><?= count($promos) ?></span>)</h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Judul Promo</th>
                                <th>Visual (Image / BG)</th>
                                <th>Badge</th>
                                <th>Masa Berlaku</th>
                                <th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($promos)): ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; padding: 2rem;">Belum ada promo.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($promos as $idx => $p): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td style="font-weight: 600;"><?= htmlspecialchars($p['title']) ?></td>
                                    <td>
                                        <?php if(!empty($p['image_url'])): ?>
                                            <span style="background:var(--success); color:white; padding:2px 6px; border-radius:4px; font-size:12px;">Yes (Gambar)</span>
                                        <?php else: ?>
                                            <span style="background:#555; color:white; padding:2px 6px; border-radius:4px; font-size:12px;">Gradient Fallback</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($p['badge_text']) ?></td>
                                    <td><?= htmlspecialchars($p['valid_until']) ?></td>
                                    <td style="text-align: right;">
                                        <a href="?action=delete&id=<?= $p['id'] ?>" onclick="return confirm('Hapus promo ini dari halaman utama?')" style="color: #d32f2f; text-decoration: none; font-weight: 600;">Hapus</a>
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
