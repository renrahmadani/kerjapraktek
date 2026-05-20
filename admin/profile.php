<?php
session_start();
require_once '../config.php';

// Proteksi Halaman Khusus Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<!DOCTYPE html><html><head><meta name='viewport' content='width=device-width, initial-scale=1.0'><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body style='background:#f8fafc;'><script>Swal.fire({icon: 'warning',title: 'Akses Ditolak',text: 'Silakan login sebagai Admin.',confirmButtonColor: '#3b82f6'}).then(() => { window.location.href='../auth.php'; });</script></body></html>";
    exit;
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['fullname'] ?? 'Administrator';

// Ambil data admin terbaru dari DB
$stmt = $pdo->prepare("SELECT fullname, username, email FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$adminData = $stmt->fetch();

if (!$adminData) {
    die("Data Admin tidak ditemukan.");
}

$error = '';
$success = '';

// Proses Update Profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_fullname = trim($_POST['fullname']);
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_password = $_POST['password'];

    // Validasi input
    if (empty($new_fullname) || empty($new_username) || empty($new_email)) {
        $error = "Nama Lengkap, Username, dan Email wajib diisi.";
    } else {
        // Cek apakah username/email dipakai oleh user lain
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmtCheck->execute([$new_username, $new_email, $admin_id]);
        if ($stmtCheck->rowCount() > 0) {
            $error = "Username atau Email sudah terdaftar pada pengguna lain.";
        } else {
            if (!empty($new_password)) {
                // Update dengan password
                $hashed_pw = password_hash($new_password, PASSWORD_DEFAULT);
                $stmtUpdate = $pdo->prepare("UPDATE users SET fullname=?, username=?, email=?, password=? WHERE id=?");
                $success_update = $stmtUpdate->execute([$new_fullname, $new_username, $new_email, $hashed_pw, $admin_id]);
            } else {
                // Update tanpa password
                $stmtUpdate = $pdo->prepare("UPDATE users SET fullname=?, username=?, email=? WHERE id=?");
                $success_update = $stmtUpdate->execute([$new_fullname, $new_username, $new_email, $admin_id]);
            }

            if ($success_update) {
                // Update Session
                $_SESSION['fullname'] = $new_fullname;
                $admin_name = $new_fullname;
                // Refresh data
                $adminData['fullname'] = $new_fullname;
                $adminData['username'] = $new_username;
                $adminData['email'] = $new_email;
                $success = "Profil berhasil diperbarui!";
            } else {
                $error = "Gagal memperbarui profil. Terjadi kesalahan server.";
            }
        }
    }
}

// Cek notifikasi belum dibaca (untuk topbar icon)
$stmt_notif = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read=0 AND user_id IS NULL");
$unread_notifs = $stmt_notif->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Admin - PT. Wahana Indo Trada</title>
    <link rel="stylesheet" href="../style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .profile-container {
            background: #fff;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            max-width: 600px;
            margin-top: 1rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--on-surface);
        }
        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--outline);
            border-radius: 0.5rem;
            font-family: inherit;
            font-size: 1rem;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-container);
        }
        .btn-update {
            background: var(--primary);
            color: #fff;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
            font-size: 1rem;
        }
        .btn-update:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="dashboard-layout">
    
    <!-- Sidebar -->
    <nav class="sidebar">
        <!-- Header -->
        <div class="sidebar-header" style="cursor: pointer;" onclick="window.location.href='../index.php'">
            <div class="sidebar-avatar">
                <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuBEFEvrc9N8gbQGXW4ldmtTaLZ3drxIRCSC4Cza4qUorQCNUy8LvLDeo0d5GgVGJSAbH_2EWOKT7N6XPWlJQRJ8VHyCNc8i-OIJ0ESWLnu7JCTFwRycxUgEk6hfZ0_jojkLo21s5W5SVO-CK_v1dY0Y2Q3xHnfk2oLpp8JPd4_IjxdHubT3ouInkD53hZ-orKvJdoVxjnOiIUfTbm0_QlRaaOnEWSTfmlAF5WA9mUtQJP9MTuYbs18XH9bM9aM5zOGcwrW6Zxbd7-_T" alt="Admin Profile">
            </div>
            <div>
                <h2 class="sidebar-title">Service Console</h2>
                <p class="sidebar-subtitle">PT. Wahana Indo Trada</p>
            </div>
        </div>

        <!-- Navigation Links -->
        <ul class="sidebar-nav">
            <li>
                <a href="dashboard.php">
                    <span class="material-symbols-outlined">dashboard</span>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="bookings.php">
                    <span class="material-symbols-outlined">calendar_month</span>
                    Bookings
                </a>
            </li>
            <li>
                <a href="promos.php">
                    <span class="material-symbols-outlined">sell</span>
                    Promotions
                </a>
            </li>
            <li>
                <a href="customers.php">
                    <span class="material-symbols-outlined">group</span>
                    Customers
                </a>
            </li>
            <li>
                <a href="reviews.php">
                    <span class="material-symbols-outlined">reviews</span>
                    Reviews
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <ul class="sidebar-nav" style="gap:0;">
                <li>
                    <a href="profile.php" class="active">
                        <span class="material-symbols-outlined">person</span>
                        Profil Admin
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0)" onclick="confirmLogout(event, '../auth.php?action=logout');">
                        <span class="material-symbols-outlined">logout</span>
                        Logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="dashboard-main">
        
        <header class="dashboard-topbar">
            <div class="topbar-title">
                <h1>Profil Admin</h1>
                <p>Kelola informasi akun dan kata sandi Anda.</p>
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
            <div class="profile-container">
                <form method="POST" action="">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($adminData['fullname']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($adminData['username']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($adminData['email']) ?>" required>
                    </div>

                    <div style="background: var(--surface-variant); padding: 1.5rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                        <h4 style="margin-top: 0; margin-bottom: 1rem; color: var(--on-surface);">Ubah Kata Sandi</h4>
                        <p style="font-size: 0.85rem; color: var(--on-surface-variant); margin-bottom: 1rem;">Biarkan kosong jika tidak ingin mengubah kata sandi.</p>
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Kata Sandi Baru</label>
                            <input type="password" name="password" class="form-control" placeholder="Masukkan sandi baru...">
                        </div>
                    </div>

                    <button type="submit" class="btn-update">Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </main>

<script>
<?php if(!empty($error)): ?>
    Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: <?= json_encode($error) ?>,
        confirmButtonColor: '#d33'
    });
<?php endif; ?>

<?php if(!empty($success)): ?>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: <?= json_encode($success) ?>,
        confirmButtonColor: '#3b82f6',
        timer: 3000
    });
<?php endif; ?>

function confirmLogout(event, url) {
    event.preventDefault();
    Swal.fire({
        title: 'Konfirmasi Logout',
        text: 'Apakah Anda yakin ingin keluar dari sesi admin?',
        icon: 'warning',
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
