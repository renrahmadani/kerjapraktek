# 🔧 Sistem Booking Servis — PT. Wahana Indo Trada

Aplikasi web booking servis kendaraan untuk bengkel **PT. Wahana Indo Trada**. Dibuat sebagai project Kerja Praktek.

Customer bisa booking jadwal servis online tanpa perlu antre, dan admin bisa kelola semua pesanan dari dashboard.

## ✨ Fitur

**Customer:**
- 📅 Booking servis online (pilih tanggal, jam, jenis servis)
- 🚗 Input multi kendaraan dalam 1 booking
- 🔔 Notifikasi status booking (Baru → Proses → Selesai)
- 📧 Email notifikasi otomatis
- ⭐ Kasih review setelah servis selesai
- 💬 Chat WhatsApp langsung ke admin

**Admin:**
- 📊 Dashboard kelola booking masuk
- ✅ Update status pesanan (Proses / Selesai / Batal)
- 👥 Lihat data customer
- 📧 Email otomatis ke customer setiap perubahan status

## 🛠️ Tech Stack

- **PHP** — Native (tanpa framework)
- **MySQL** — Database
- **CSS** — Custom design, responsive, dark/light ready
- **PHPMailer** — Kirim email via Gmail SMTP
- **SweetAlert2** — Popup & notifikasi interaktif
- **Laragon** — Local development server

## 📦 Cara Install

1. **Clone repo ini**
   ```bash
   git clone https://github.com/renrahmadani/kerjapraktek.git
   cd kerjapraktek
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Setup konfigurasi**
   ```bash
   copy env.example.php env.php
   ```
   Lalu edit `env.php`, isi dengan data kamu:
   - Email & App Password Gmail (untuk kirim notifikasi)
   - Nomor WhatsApp admin

4. **Jalankan di web server** (Laragon / XAMPP)
   - Pastikan Apache & MySQL sudah running
   - Akses `http://localhost/kerjapraktek/`

5. **Inisialisasi database**
   - Buka `http://localhost/kerjapraktek/init_db.php`
   - Database & tabel akan otomatis dibuat

6. **Login Admin**
   ```
   Username: admin
   Password: admin123
   ```

## 📁 Struktur Project

```
kerjapraktek/
├── admin/              # Halaman admin (dashboard, kelola booking)
├── assets/             # Gambar & media
├── vendor/             # Dependencies (auto-generate dari composer)
├── config.php          # Koneksi database & setup email
├── env.php             # Data sensitif (TIDAK di-push ke GitHub)
├── env.example.php     # Template konfigurasi
├── index.php           # Landing page
├── auth.php            # Login & register
├── booking.php         # Halaman booking servis
├── notifications.php   # Halaman notifikasi
├── profile.php         # Profil user
├── katalog_promos.php  # Daftar promo
├── detail.php          # Detail layanan
├── init_db.php         # Setup database & data dummy
└── style.css           # Semua styling
```

## ⚙️ Konfigurasi Email

Aplikasi ini pakai **Gmail SMTP** untuk kirim email. Untuk setup:

1. Buka [Google App Passwords](https://myaccount.google.com/apppasswords)
2. Generate App Password baru
3. Masukkan email & app password ke `env.php`

## 📝 Lisensi

Project ini dibuat untuk keperluan **Kerja Praktek** di PT. Wahana Indo Trada.

---
*Didevelop oleh Ren Rahmadani — 2026*
