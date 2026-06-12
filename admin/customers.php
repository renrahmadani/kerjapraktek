<?php
session_start();
require_once '../config.php';

// Proteksi Halaman
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<!DOCTYPE html><html><head><meta name='viewport' content='width=device-width, initial-scale=1.0'><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body style='background:#f8fafc;'><script>Swal.fire({icon: 'warning',title: 'Akses Ditolak',text: 'Silakan login sebagai Admin.',confirmButtonColor: '#3b82f6'}).then(() => { window.location.href='../auth.php'; });</script></body></html>";
    exit;
}

$admin_name = $_SESSION['fullname'] ?? 'Admin';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $customer_id = $_POST['customer_id'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    if (!empty($customer_id) && !empty($new_password)) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=? AND role='customer'");
        if ($stmt->execute([$hashed, $customer_id])) {
            $msg = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Berhasil!', 'Password pelanggan berhasil diubah.', 'success'); });</script>";
        } else {
            $msg = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Gagal!', 'Terjadi kesalahan sistem.', 'error'); });</script>";
        }
    }
}

// Handle Delete Customer
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    // Hapus customer (Bisa dicegah jika ingin mempertahankan riwayat integrasi tabel child)
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    header("Location: customers.php");
    exit;
}

// Fetch Semua Customers
$customers = [];
try {
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'customer' ORDER BY id DESC");
    $customers = $stmt->fetchAll();
} catch (PDOException $e) { /* ignore */ }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pelanggan - Wahana Indo Trada</title>
    <link rel="stylesheet" href="../style.css?v=1.4">
    <style>
        .initials-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; color: white;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            flex-shrink: 0;
        }
    </style>
</head>
<body class="dashboard-layout">
    
    <nav class="sidebar">
        <!-- Header -->
        <div class="sidebar-header" style="cursor:pointer;" onclick="window.location.href='dashboard.php'">
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
            <li><a href="customers.php" class="active"><span class="material-symbols-outlined">group</span>Customers</a></li>
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

    <main class="dashboard-main">
        <header class="dashboard-topbar">
            <div class="topbar-title">
                <h1>Daftar Pelanggan</h1>
                <p>Kelola data registrasi seluruh pelanggan sistem.</p>
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
                    <h2>Registrasi Pengguna Sistem (<span style="color:var(--primary);"><?= count($customers) ?></span>)</h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>No. Handphone (WA)</th>
                                <th>Bergabung Sejak</th>
                                <th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($customers)): ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; padding: 2rem;">Belum ada pelanggan terdaftar.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($customers as $idx => $c): 
                                    $words = explode(" ", $c['fullname']);
                                    $ini = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                                ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <div class="table-customer">
                                            <div class="initials-avatar"><?= htmlspecialchars($ini) ?></div>
                                            <span style="font-weight: 600; color: var(--primary);"><?= htmlspecialchars($c['fullname']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($c['username']) ?></td>
                                    <td><?= htmlspecialchars($c['email'] ?? 'Tidak ada data') ?></td>
                                    <td style="font-weight: 500;"><?= htmlspecialchars($c['no_hp'] ?? 'Tidak ada data') ?></td>
                                    <td style="color: var(--on-surface-variant);"><?= date('d M Y, H:i', strtotime($c['created_at'])) ?></td>
                                    <td style="text-align: right;">
                                        <a href="javascript:void(0)" onclick="editPassword(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['fullname'])) ?>')" style="color: var(--primary); text-decoration: none; font-weight: 600; margin-right: 10px;">Edit Password</a>
                                        <a href="?action=delete&id=<?= $c['id'] ?>" onclick="return confirm('Peringatan: Menghapus pelanggan bisa menghilangkan riwayat pesanan mereka dari layar! Lanjut?')" style="color: #d32f2f; text-decoration: none; font-weight: 600;">Hapus Akun</a>
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
    <?= $msg ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<form id="formEditPassword" method="POST" style="display: none;">
    <input type="hidden" name="action" value="change_password">
    <input type="hidden" name="customer_id" id="ep_customer_id">
    <input type="hidden" name="new_password" id="ep_new_password">
</form>
<script>
function editPassword(id, name) {
    Swal.fire({
        title: 'Edit Password',
        text: 'Masukkan kata sandi baru untuk pelanggan ' + name,
        input: 'password',
        inputAttributes: {
            autocapitalize: 'off',
            placeholder: 'Password Baru',
            required: 'true'
        },
        showCancelButton: true,
        confirmButtonText: 'Simpan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#3b82f6',
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            document.getElementById('ep_customer_id').value = id;
            document.getElementById('ep_new_password').value = result.value;
            document.getElementById('formEditPassword').submit();
        }
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
