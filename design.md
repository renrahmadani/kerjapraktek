# 🏗️ System Design: Sistem Booking Servis — PT. Wahana Indo Trada

Dokumen ini menjelaskan arsitektur, struktur, dan desain keseluruhan dari Sistem Booking Servis kendaraan untuk bengkel PT. Wahana Indo Trada. Sistem ini dibangun dengan pendekatan prosedural (Native PHP) yang dirancang agar ringan, responsif, dan mudah dikembangkan.

---

## 1. 🛠️ Tech Stack & Arsitektur

Aplikasi ini menggunakan arsitektur Monolithic sederhana berbasis server-side rendering (SSR) tanpa menggunakan framework besar, demi menjaga performa dan kemudahan instalasi di lingkungan _local development_ maupun _shared hosting_.

*   **Backend:** PHP Native (Versi 7.4 / 8.x)
*   **Database:** MySQL / MariaDB (via PDO)
*   **Frontend:** HTML5, CSS3 (Custom dengan CSS Variables, mendukung Dark/Light mode), Vanilla JavaScript
*   **Libraries Eksternal:**
    *   **SweetAlert2:** Digunakan untuk notifikasi interaktif, konfirmasi popup, dan alert (menggantikan alert standar browser).
    *   **PHPMailer:** Digunakan untuk mengirim email notifikasi status booking menggunakan protokol SMTP (Gmail).
*   **Environment:** Laragon / XAMPP (Local Server)
*   **Package Manager:** Composer (hanya untuk mengelola dependensi `vendor/` seperti PHPMailer).

---

## 2. 🗄️ Database Schema (Skema Database)

Database dirancang terstruktur (Relational Database) dengan tabel-tabel utama sebagai berikut:

### `users` (Data Pengguna & Autentikasi)
Tabel ini menyimpan data baik customer maupun admin.
*   `id` (INT, PK): ID Unik.
*   `fullname` (VARCHAR): Nama lengkap pengguna.
*   `username` (VARCHAR): Username untuk login (unik).
*   `email` (VARCHAR): Email pengguna (untuk notifikasi).
*   `no_hp` (VARCHAR): Nomor WhatsApp/Telepon.
*   `password` (VARCHAR): Password yang di-hash menggunakan `password_hash()`.
*   `role` (ENUM): Peran pengguna (`customer` atau `admin`).

### `services` (Layanan Servis)
Daftar layanan yang ditawarkan bengkel.
*   `id` (INT, PK)
*   `kategori` (ENUM): Kategori servis (`General` atau `Body Repair`).
*   `title`, `icon`, `image_url` (VARCHAR): Info visual layanan.
*   `harga_estimasi` (DECIMAL): Estimasi harga dasar.
*   `features` (JSON): Detail fitur layanan (disimpan dalam format JSON array).

### `promos` (Katalog Promosi)
Daftar promo bengkel yang akan ditampilkan di beranda.
*   `id` (INT, PK)
*   `title`, `description` (TEXT): Judul dan deskripsi promo.
*   `badge_text`, `discount_text` (VARCHAR): Teks penanda diskon.
*   `badge_type` (ENUM): Status promo (`active` atau `soon`).
*   `bg_gradient_start`, `bg_gradient_end` (VARCHAR): Warna visual card promo (menggunakan CSS Variables).
*   `valid_until` (VARCHAR): Masa berlaku promo.

### `bookings` (Transaksi Booking)
Tabel paling krusial yang menghubungkan customer dengan layanan yang dipesan.
*   `id` (INT, PK)
*   `booking_code` (VARCHAR): Kode unik booking (misal: B-1042).
*   `user_id` (INT, FK): Merujuk ke tabel `users`.
*   `service_id` (INT, FK): Merujuk ke tabel `services`.
*   `customer_name`, `customer_initials` (VARCHAR): Data redundan untuk mempercepat query tampilan.
*   `tgl_booking` (DATE), `jam_booking` (TIME): Jadwal servis.
*   `kendaraan_details` (LONGTEXT): Array JSON berisi multi-kendaraan (Nama & Plat) dalam satu booking.
*   `keluhan` (TEXT): Keluhan pelanggan.
*   `status` (ENUM): Status pesanan (`Baru`, `Proses`, `Selesai`, `Batal`).
*   `created_at`, `proses_at`, `selesai_at`, `batal_at` (TIMESTAMP): Log tracking waktu untuk tiap tahapan pesanan.

### `notifications` (Sistem Notifikasi)
Pesan notifikasi in-app untuk pengguna (misal: "Booking Anda sedang diproses").
*   `id` (INT, PK)
*   `user_id` (INT, FK): Penerima notifikasi.
*   `title`, `message` (TEXT)
*   `is_read` (TINYINT): Status sudah dibaca atau belum.

### `reviews` (Ulasan Pelanggan)
Feedback dari pelanggan setelah servis selesai.
*   `id` (INT, PK)
*   `booking_id` (INT, FK), `user_id` (INT, FK)
*   `rating` (INT): Nilai bintang (1-5).
*   `comment` (TEXT): Ulasan teks.
*   `status` (ENUM): Moderasi ulasan (`pending`, `approved`, `rejected`).

---

## 3. 👥 User Roles & Flow

Sistem ini memiliki dua peran utama dengan alur kerja (flow) yang berbeda:

### A. Customer (Pelanggan)
1.  **Registrasi & Autentikasi:** Mendaftar akun, login (`auth.php`).
2.  **Eksplorasi:** Melihat daftar layanan (`detail.php`), katalog promo (`katalog_promos.php`).
3.  **Booking:** Melakukan pemesanan (`booking.php`), memasukkan jadwal dan mendata satu atau lebih kendaraan sekaligus.
4.  **Tracking & Notif:** Menerima notifikasi di dashboard/email atas status booking (`notifications.php`).
5.  **Review:** Memberikan ulasan pasca servis (`reviews.php`).

### B. Administrator
1.  **Dashboard Utama (`admin/dashboard.php`):** Melihat statistik ringkas (Total Booking, Booking Baru, Selesai).
2.  **Manajemen Booking (`admin/bookings.php`):** 
    *   Melihat daftar antrean.
    *   Mengubah status (`Baru` -> `Proses` -> `Selesai` atau `Batal`). Proses ini akan men-trigger notifikasi in-app dan pengiriman email otomatis (via PHPMailer) ke customer.
3.  **Manajemen Customer (`admin/customers.php`):** Melihat basis data pelanggan.
4.  **Manajemen Promo & Layanan (`admin/promos.php`):** (Direncanakan/Tersedia) untuk mengelola data master.
5.  **Manajemen Ulasan (`admin/reviews.php`):** Memoderasi (approve/reject) ulasan dari pelanggan sebelum tampil di publik.
6.  **Manajemen Profil Admin (`admin/profile.php`):** Mengelola kredensial login admin secara aman.

---

## 4. 📁 Struktur Direktori

Sistem ini disusun dengan pemisahan wilayah publik (customer) dan privat (admin):

```
kerjapraktek/
│
├── admin/                  # Zona terproteksi (Khusus Admin)
│   ├── dashboard.php       # Ringkasan statistik
│   ├── bookings.php        # CRUD transaksi pesanan
│   ├── customers.php       # Data pengguna
│   ├── promos.php          # Kelola promo
│   ├── reviews.php         # Moderasi ulasan
│   └── profile.php         # Profil administrator
│
├── assets/                 # Gambar statis, ikon, dan logo
├── vendor/                 # Dependensi Composer (seperti PHPMailer)
│
├── config.php              # Inisialisasi koneksi Database (PDO) & Helper fungsi email
├── env.php                 # Environment variables (DB credentials, SMTP Gmail)
├── init_db.php             # Script migrasi & seeder (Generate tabel & data dummy)
│
├── index.php               # Halaman Landing (Beranda)
├── auth.php                # Form Login & Registrasi (User & Admin via Role)
├── booking.php             # Form pembuatan pesanan bagi customer
├── profile.php             # Halaman profil customer
├── notifications.php       # Inbox notifikasi customer
├── katalog_promos.php      # Halaman list promosi
├── detail.php              # Halaman detail layanan
└── style.css               # File CSS terpusat (Sistem Design Token / CSS Variables)
```

---

## 5. 🎨 Design System (Sistem Desain)

Aplikasi ini menggunakan pendekatan CSS kustom yang berpusat pada file `style.css`. 
*   **CSS Variables:** Menggunakan variabel CSS (`--primary`, `--surface`, `--on-surface`, dsb.) untuk memastikan konsistensi warna (Material Design / M3 style).
*   **Responsivitas:** Mengandalkan Flexbox dan CSS Grid, dipadukan dengan Media Queries untuk memastikan UI tampak rapi di layar Desktop maupun Mobile.
*   **Micro-interactions:** Implementasi transisi halus (hover states, modal animasi) dan *glassmorphism* tipis pada beberapa komponen panel.

---

## 6. 🔒 Keamanan (Security Features)

1.  **Prepared Statements:** Seluruh interaksi database dalam sistem menggunakan PDO (PHP Data Objects) dengan fitur **Prepared Statements** (binding parameters) untuk mencegah ancaman **SQL Injection**.
2.  **Password Hashing:** Password disimpan dengan enkripsi searah menggunakan algoritma `bcrypt` via fungsi bawaan PHP `password_hash()` dan divalidasi dengan `password_verify()`.
3.  **Authentication Check:** Setiap halaman terproteksi memiliki pengecekan `$_SESSION` di baris pertama, memaksa _unauthorized users_ kembali ke halaman login.
4.  **Environment Variables:** Pemisahan file konfigurasi sensitif (seperti password SMTP) ke dalam file `env.php` yang diabaiakan oleh Git (`.gitignore`).

---

## 7. 🚀 Rangkuman Alur Kerja Kode (Code Workflow)

Setiap proses utama mengadopsi pola _Post-Redirect-Get_ (PRG):
1.  User submit Form (POST).
2.  File PHP memvalidasi input dan mengeksekusi query database.
3.  Jika terjadi perubahan status krusial (misal: Admin merubah status booking menjadi selesai), sistem memanggil helper pengirim email via PHPMailer.
4.  Sistem menset pesan sukses (menggunakan Session Flash Data / JSON Response).
5.  Halaman di-redirect / direfresh untuk menampilkan data terbaru (dan SweetAlert menampilkan pesan flash tersebut di layar).
