<?php
session_start();
require_once 'config.php';

// Khusus Logika Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: auth.php");
    exit;
}

// Redirect jika sudah login
if (isset($_SESSION['user_id']) && !isset($_POST['action'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
        exit;
    } else {
        header("Location: index.php");
        exit;
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $fullname = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $no_hp = trim($_POST['no_hp'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!empty($fullname) && !empty($username) && !empty($email) && !empty($no_hp) && !empty($password)) {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = "Username atau email sudah terdaftar.";
            } else {
                // Insert user
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (fullname, username, email, no_hp, password, role) VALUES (?, ?, ?, ?, ?, 'customer')");
                if ($stmt->execute([$fullname, $username, $email, $no_hp, $hash])) {
                    $success = "Registrasi berhasil! Silakan login.";
                } else {
                    $error = "Gagal mendaftar, coba lagi.";
                }
            }
        } else {
            $error = "Harap isi semua kolom pendaftaran!";
        }
    } elseif ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!empty($username) && !empty($password)) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['fullname'] = $user['fullname'];
                
                $target_url = ($user['role'] === 'admin') ? 'admin/dashboard.php' : 'index.php';
                $role_label = ($user['role'] === 'admin') ? 'Admin' : 'Pelanggan';
                echo "<!DOCTYPE html>
                <html lang='id'>
                <head>
                    <meta charset='utf-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Login Berhasil</title>
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <style>
                        body { background: #fcf8ff; font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
                    </style>
                </head>
                <body>
                    <script>
                        Swal.fire({
                            title: 'Login Berhasil!',
                            text: 'Selamat datang kembali, " . addslashes($user['fullname']) . " (" . $role_label . ")',
                            icon: 'success',
                            timer: 2000,
                            timerProgressBar: true,
                            showConfirmButton: false,
                            allowOutsideClick: false
                        }).then(() => {
                            window.location.href = '$target_url';
                        });
                    </script>
                </body>
                </html>";
                exit;
            } else {
                $error = "Username atau password salah.";
            }
        } else {
            $error = "Harap isi username dan password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk / Daftar - PT. Wahana Indo Trada</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="auth-body">

<main class="auth-container">
    
    <!-- Left Side: Branding / Image -->
    <div class="auth-left">
        <div class="auth-bg-overlay" style="background-image: url('assets/auth_bg.png');"></div>
        
        <div class="auth-brand">
            <img src="logo.png" alt="PT. Wahana Indo Trada" style="height: 45px; width: auto; margin-bottom: 1rem; filter: brightness(0) invert(1);" onerror="this.onerror=null; this.src=''; this.alt='Wahana Indo Trada';">
            <p>Elevating your automotive service experience. Precision, hospitality, and performance.</p>
        </div>

        <div class="auth-badge">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">verified</span>
            <span>Authorized Service Center</span>
        </div>
    </div>

    <!-- Right Side: Forms -->
    <div class="auth-right">
        
        <div class="auth-mobile-brand">
            <img src="logo.png" alt="PT. Wahana Indo Trada" style="height: 35px; width: auto;" onerror="this.onerror=null; this.src=''; this.alt='Wahana Indo Trada';">
        </div>

        <div class="auth-header" id="authHeader">
            <h2 id="headerTitle">Welcome Back</h2>
            <p id="headerDesc">Sign in to your concierge dashboard.</p>
        </div>

        <?php if($error): ?>
            <div style="background: var(--error-container); color: var(--on-error-container); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-weight: 500;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div style="background: #e2fce6; color: #0d4a1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-weight: 500;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- Content Tabs -->
        <div class="auth-tabs">
            <button class="auth-tab active" id="tabLogin" onclick="switchTab('login')">LOGIN</button>
            <button class="auth-tab" id="tabRegister" onclick="switchTab('register')">REGISTER</button>
        </div>

        <!-- Login Form -->
        <div class="auth-form-wrap active" id="formLogin">
            <form action="auth.php" method="POST">
                <input type="hidden" name="action" value="login">
                
                <div class="auth-input-group">
                    <label for="login-username" class="auth-label">Username / No. HP</label>
                    <div class="auth-input-wrapper">
                        <span class="material-symbols-outlined auth-icon">person</span>
                        <input type="text" id="login-username" name="username" class="auth-input" placeholder="Enter your username" required>
                    </div>
                </div>

                <div class="auth-input-group">
                    <label for="login-password" class="auth-label">Password</label>
                    <div class="auth-input-wrapper">
                        <span class="material-symbols-outlined auth-icon">lock</span>
                        <input type="password" id="login-password" name="password" class="auth-input" placeholder="Enter your password" required>
                        <button type="button" class="auth-pw-toggle" onclick="togglePassword('login-password')">
                            <span class="material-symbols-outlined">visibility</span>
                        </button>
                    </div>
                </div>

                <div class="auth-options">
                    <div class="auth-checkbox-wrap">
                        <input type="checkbox" id="remember-me" class="auth-checkbox">
                        <label for="remember-me" class="auth-checkbox-label">Remember me</label>
                    </div>
                    <a href="#" class="auth-forgot">Forgot password?</a>
                </div>

                <button type="submit" class="auth-btn">Masuk</button>
            </form>
        </div>

        <!-- Register Form -->
        <div class="auth-form-wrap" id="formRegister">
            <form action="auth.php" method="POST">
                <input type="hidden" name="action" value="register">

                <div class="auth-input-group">
                    <label for="reg-fullname" class="auth-label">Nama Lengkap</label>
                    <div class="auth-input-wrapper">
                        <span class="material-symbols-outlined auth-icon">badge</span>
                        <input type="text" id="reg-fullname" name="fullname" class="auth-input" placeholder="John Doe" required>
                    </div>
                </div>

                <div class="auth-input-group">
                    <label for="reg-username" class="auth-label">Username</label>
                    <div class="auth-input-wrapper">
                        <span class="material-symbols-outlined auth-icon">person</span>
                        <input type="text" id="reg-username" name="username" class="auth-input" placeholder="contoh_username" required>
                    </div>
                </div>

                <div class="auth-input-group">
                    <label for="reg-email" class="auth-label">Email</label>
                    <div class="auth-input-wrapper">
                        <span class="material-symbols-outlined auth-icon">mail</span>
                        <input type="email" id="reg-email" name="email" class="auth-input" placeholder="contoh@email.com" required>
                    </div>
                </div>

                <div class="auth-input-group">
                    <label for="reg-no-hp" class="auth-label">No. HP (WhatsApp)</label>
                    <div class="auth-input-wrapper">
                        <span class="material-symbols-outlined auth-icon">call</span>
                        <input type="text" id="reg-no-hp" name="no_hp" class="auth-input" placeholder="08123xxxx" required>
                    </div>
                </div>

                <div class="auth-input-group">
                    <label for="reg-password" class="auth-label">Password</label>
                    <div class="auth-input-wrapper">
                        <span class="material-symbols-outlined auth-icon">lock</span>
                        <input type="password" id="reg-password" name="password" class="auth-input" placeholder="Buat password" required>
                        <button type="button" class="auth-pw-toggle" onclick="togglePassword('reg-password')">
                            <span class="material-symbols-outlined">visibility</span>
                        </button>
                    </div>
                </div>

                <button type="submit" class="auth-btn">Daftar Sekarang</button>
            </form>
        </div>

        <div class="auth-footer">
            <p><a href="index.php" style="color:var(--primary); font-weight:600; text-decoration:none;">← Kembali ke Beranda</a></p>
            <?php if(isset($_SESSION['user_id'])): ?>
                <p style="margin-top:1rem;"><a href="?action=logout" style="color:var(--error); font-weight:bold;">Tutup Sesi (Logout)</a></p>
            <?php endif; ?>
        </div>

    </div>

</main>

<script>
    function switchTab(tab) {
        const loginTab = document.getElementById('tabLogin');
        const regTab = document.getElementById('tabRegister');
        const loginForm = document.getElementById('formLogin');
        const regForm = document.getElementById('formRegister');
        
        const hTitle = document.getElementById('headerTitle');
        const hDesc = document.getElementById('headerDesc');

        if (tab === 'login') {
            loginTab.classList.add('active');
            regTab.classList.remove('active');
            loginForm.classList.add('active');
            regForm.classList.remove('active');
            
            hTitle.innerText = "Welcome Back";
            hDesc.innerText = "Sign in to your concierge dashboard.";
        } else {
            regTab.classList.add('active');
            loginTab.classList.remove('active');
            regForm.classList.add('active');
            loginForm.classList.remove('active');

            hTitle.innerText = "Create Account";
            hDesc.innerText = "Register for priority booking service.";
        }
    }

    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        if(input.type === 'password') {
            input.type = 'text';
        } else {
            input.type = 'password';
        }
    }
</script>

<?php if(!empty($error)): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: <?= json_encode($error) ?>,
        confirmButtonColor: 'var(--secondary)'
    });
</script>
<?php endif; ?>

<?php if(!empty($success)): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: <?= json_encode($success) ?>,
        confirmButtonColor: '#25D366'
    }).then(() => {
        switchTab('login');
    });
</script>
<?php endif; ?>

</body>
</html>
