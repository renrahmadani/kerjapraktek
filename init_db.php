<?php
require_once 'config.php';

try {
    // Drop existing tables for a clean slate during development
    $pdo->exec("DROP TABLE IF EXISTS reviews");
    $pdo->exec("DROP TABLE IF EXISTS notifications");
    $pdo->exec("DROP TABLE IF EXISTS bookings");
    $pdo->exec("DROP TABLE IF EXISTS promos");
    $pdo->exec("DROP TABLE IF EXISTS services");
    $pdo->exec("DROP TABLE IF EXISTS users");

    // 1. Buat Tabel Services
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            kategori ENUM('General', 'Body Repair') NOT NULL,
            title VARCHAR(100) NOT NULL,
            icon VARCHAR(50) NOT NULL,
            image_url VARCHAR(500) NOT NULL,
            harga_estimasi DECIMAL(10,2) DEFAULT 0.00,
            features JSON NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Cek apakah data services sudah ada
    $stmt = $pdo->query("SELECT COUNT(*) FROM services");
    if ($stmt->fetchColumn() == 0) {
        // Insert Data Dummy Services
        $services = [
            [
                'kategori' => 'General',
                'title' => 'General Repair',
                'icon' => 'build',
                'image_url' => 'assets/general_repair.png',
                'harga_estimasi' => 500000,
                'features' => json_encode(['Servis Berkala & Ganti Oli', 'Pengecekan Mesin Komprehensif', 'Perawatan Rem & Suspensi'])
            ],
            [
                'kategori' => 'Body Repair',
                'title' => 'Body Repair',
                'icon' => 'format_paint',
                'image_url' => 'assets/body_repair.png',
                'harga_estimasi' => 1500000,
                'features' => json_encode(['Perbaikan Penyok & Goresan', 'Pengecatan Ulang Full Body', 'Poles & Detailing Premium'])
            ]
        ];

        $stmt = $pdo->prepare("INSERT INTO services (kategori, title, icon, image_url, harga_estimasi, features) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($services as $svc) {
            $stmt->execute([$svc['kategori'], $svc['title'], $svc['icon'], $svc['image_url'], $svc['harga_estimasi'], $svc['features']]);
        }
    }

    // 2. Buat Tabel Promos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS promos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(100) NOT NULL,
            description TEXT NOT NULL,
            badge_text VARCHAR(50) NOT NULL,
            badge_type ENUM('active', 'soon') DEFAULT 'active',
            discount_text VARCHAR(50) NOT NULL,
            bg_gradient_start VARCHAR(50) NOT NULL,
            bg_gradient_end VARCHAR(50) NOT NULL,
            image_url VARCHAR(500),
            valid_until VARCHAR(100) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Cek apakah data promos sudah ada
    $stmt = $pdo->query("SELECT COUNT(*) FROM promos");
    if ($stmt->fetchColumn() == 0) {
        $promos = [
            [
                'title' => 'Paket Ganti Oli Musim Hujan',
                'description' => 'Dapatkan diskon 20% untuk paket ganti oli sintetis + filter + pengecekan 20 titik.',
                'badge_text' => 'AKTIF',
                'badge_type' => 'active',
                'discount_text' => 'DISC 20%',
                'bg_gradient_start' => 'var(--primary-container)', // Will map to css vars
                'bg_gradient_end' => 'var(--primary)',
                'image_url' => '',
                'valid_until' => 'Berlaku s/d 30 Nov 2023'
            ],
            [
                'title' => 'Gratis Spooring Balancing',
                'description' => 'Beli 4 ban baru merk apa saja, gratis layanan spooring dan balancing.',
                'badge_text' => 'AKTIF',
                'badge_type' => 'active',
                'discount_text' => 'FREE',
                'bg_gradient_start' => 'var(--tertiary-container)',
                'bg_gradient_end' => 'var(--tertiary)',
                'image_url' => '',
                'valid_until' => 'Berlaku s/d 15 Des 2023'
            ],
            [
                'title' => 'Promo Servis Akhir Tahun',
                'description' => 'Persiapkan kendaraan untuk liburan. Detail promo akan segera diumumkan.',
                'badge_text' => 'SEGERA',
                'badge_type' => 'soon',
                'discount_text' => 'AKHIR TAHUN',
                'bg_gradient_start' => 'var(--surface-tint)',
                'bg_gradient_end' => 'var(--inverse-surface)',
                'image_url' => '',
                'valid_until' => 'Mulai 1 Des 2023'
            ]
        ];

        $stmt = $pdo->prepare("INSERT INTO promos (title, description, badge_text, badge_type, discount_text, bg_gradient_start, bg_gradient_end, image_url, valid_until) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($promos as $promo) {
            $stmt->execute([
                $promo['title'], $promo['description'], $promo['badge_text'], $promo['badge_type'],
                $promo['discount_text'], $promo['bg_gradient_start'], $promo['bg_gradient_end'], $promo['image_url'], $promo['valid_until']
            ]);
        }
    }
    // 3. Buat Tabel Users
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fullname VARCHAR(100) NOT NULL,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL,
            no_hp VARCHAR(20),
            password VARCHAR(255) NOT NULL,
            role ENUM('customer', 'admin') DEFAULT 'customer',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Insert Default Admin
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    if ($stmt->fetchColumn() == 0) {
        $admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (fullname, username, email, no_hp, password, role) VALUES ('Administrator', 'admin', '" . ADMIN_EMAIL . "', '" . ADMIN_PHONE . "', '$admin_pass', 'admin')");
    }

    // 4. Buat Tabel Bookings (untuk Admin Dashboard)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_code VARCHAR(20) NOT NULL UNIQUE,
            user_id INT,
            customer_name VARCHAR(100) NOT NULL,
            customer_initials VARCHAR(10) NOT NULL,
            service_id INT,
            service_name VARCHAR(100) NOT NULL,
            tgl_booking DATE NOT NULL,
            jam_booking TIME NOT NULL,
            kendaraan_details LONGTEXT NOT NULL,
            keluhan TEXT,
            status ENUM('Baru', 'Proses', 'Selesai', 'Batal') DEFAULT 'Baru',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            proses_at TIMESTAMP NULL DEFAULT NULL,
            selesai_at TIMESTAMP NULL DEFAULT NULL,
            batal_at TIMESTAMP NULL DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
    if ($stmt->fetchColumn() == 0) {
        $bookings = [
            ['B-1042', 1, 'Ahmad Surya', 'AS', 1, 'Servis Berkala 10.000 KM', date('Y-m-d'), '10:00:00', '[{"nama":"Toyota Avanza", "plat":"B 1234 CD"}]', 'Ganti Oli', 'Baru'],
            ['B-1041', 1, 'Budi Waseso', 'BW', 1, 'Ganti Oli & Filter', date('Y-m-d', strtotime('+1 day')), '08:30:00', '[{"nama":"Honda Brio", "plat":"D 5678 EF"}]', 'Mesin agak kasar', 'Proses'],
            ['B-1040', 1, 'Citra Dewi', 'CD', 2, 'Pengecekan AC', date('Y-m-d', strtotime('+2 days')), '14:00:00', '[{"nama":"Suzuki Ertiga", "plat":"F 9012 GH"}]', 'AC kurang dingin', 'Proses']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO bookings (booking_code, user_id, customer_name, customer_initials, service_id, service_name, tgl_booking, jam_booking, kendaraan_details, keluhan, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($bookings as $b) {
            $stmt->execute($b);
        }
    }

    // 5. Buat Tabel Notifications
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL, 
            title VARCHAR(100) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 6. Buat Tabel Reviews
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id INT NOT NULL,
            user_id INT NOT NULL,
            customer_name VARCHAR(100) NOT NULL,
            customer_initials VARCHAR(10),
            rating INT NOT NULL DEFAULT 5,
            comment TEXT NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $stmt = $pdo->query("SELECT COUNT(*) FROM reviews");
    if ($stmt->fetchColumn() == 0) {
        $reviews = [
            [
                'booking_id' => 1, 'user_id' => 1, 'customer_name' => 'Budi Setiawan', 'customer_initials' => 'BS',
                'rating' => 5, 'comment' => 'Servisnya sangat cepat dan montirnya ramah sekali. Ruang tunggu sangat nyaman.', 'status' => 'approved'
            ],
            [
                'booking_id' => 2, 'user_id' => 2, 'customer_name' => 'Siti Nurhaliza', 'customer_initials' => 'SN',
                'rating' => 4, 'comment' => 'Pengerjaan body repair sangat rapi seperti baru kembali. Hanya saja antrean cukup panjang di akhir pekan.', 'status' => 'approved'
            ],
            [
                'booking_id' => 3, 'user_id' => 3, 'customer_name' => 'Andi Wijaya', 'customer_initials' => 'AW',
                'rating' => 5, 'comment' => 'Harga transparan sejak awal, tidak ada biaya siluman. Sangat recommended!', 'status' => 'approved'
            ]
        ];

        $stmt = $pdo->prepare("INSERT INTO reviews (booking_id, user_id, customer_name, customer_initials, rating, comment, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($reviews as $rev) {
            $stmt->execute([ $rev['booking_id'], $rev['user_id'], $rev['customer_name'], $rev['customer_initials'], $rev['rating'], $rev['comment'], $rev['status'] ]);
        }
    }

    echo "Database & Tabel berhasil diinisialisasi beserta data dummy (Termasuk Notif & Ulasan). Default Admin => username: admin, pass: admin123";

} catch (PDOException $e) {
    die("Error Exception: " . $e->getMessage());
}
?>
