# 📚 Perpustakaan Digital

Perpustakaan Digital adalah aplikasi manajemen perpustakaan berbasis **PHP Native** yang 
dirancang untuk membantu petugas perpustakaan dalam mengelola seluruh kegiatan perpustakaan 
secara digital. Aplikasi ini hadir sebagai solusi modern untuk menggantikan sistem pencatatan 
manual yang tidak efisien dan rawan kesalahan.

Dengan fitur unggulan ekspor dan impor data menggunakan library **PhpOffice PhpSpreadsheet**,
petugas dapat dengan mudah mengunduh data dalam format Excel maupun menginput data secara 
massal melalui file Excel tanpa perlu memasukkan satu per satu secara manual.

## ✨ Fitur Utama

- 📖 **Manajemen Buku** — Tambah, edit, hapus, dan cari data buku berdasarkan judul,
  kategori, maupun penulis
- 👤 **Manajemen Anggota** — Kelola data anggota perpustakaan lengkap dengan informasi
  kontak dan riwayat peminjaman
- 🔄 **Peminjaman & Pengembalian** — Catat transaksi peminjaman dan pengembalian buku
  secara tertib dan terstruktur
- 📤 **Ekspor Excel** — Download seluruh data buku, anggota, maupun transaksi peminjaman
  ke dalam file Excel menggunakan PhpOffice PhpSpreadsheet
- 📥 **Impor Excel** — Input data secara massal melalui file Excel sehingga lebih cepat
  dan efisien
- 🔐 **Autentikasi Pengguna** — Sistem login sederhana agar hanya petugas yang berwenang
  dapat mengakses dan mengelola data

## 🛠️ Teknologi yang Digunakan

| Teknologi | Keterangan |
|-----------|------------|
| PHP Native | Bahasa pemrograman utama tanpa framework |
| MySQL | Database untuk penyimpanan data aplikasi |
| PhpOffice PhpSpreadsheet | Library untuk fitur ekspor dan impor file Excel |
| Composer | Package manager untuk mengelola dependensi PHP |
| Bootstrap/CSS | Untuk tampilan antarmuka yang rapi dan responsif |
| Laragon | Server lokal untuk pengembangan di Windows |

## 📦 Dependensi
```json
{
    "require": {
        "phpoffice/phpspreadsheet": "^1.x"
    }
}
```

## ⚙️ Cara Instalasi & Menjalankan Aplikasi

Pastikan sudah menginstall **PHP**, **Composer**, dan **MySQL** di komputer kamu.
```bash
# 1. Clone repository ini
git clone https://github.com/RakaPutraAL/PerpustakaanDigital.git

# 2. Masuk ke folder projek
cd PerpustakaanDigital

# 3. Install dependensi PhpSpreadsheet via Composer
composer install

# 4. Import database
# Buka phpMyAdmin → buat database baru → import file .sql yang tersedia

# 5. Sesuaikan konfigurasi database
# Edit file koneksi database (misalnya koneksi.php atau config.php)
# Sesuaikan host, nama database, username, dan password

# 6. Jalankan aplikasi
# Buka Laragon → Start All → akses di browser
# http://localhost/PerpustakaanDigital
```

## 🗂️ Struktur Folder
```
PerpustakaanDigital/
├── vendor/          # Dependensi Composer (PhpSpreadsheet)
├── assets/          # File CSS, JS, dan gambar
├── includes/        # File koneksi database dan fungsi umum
├── exports/         # File untuk handle ekspor Excel
├── imports/         # File untuk handle impor Excel
├── database/        # File SQL untuk import database
├── composer.json    # Konfigurasi Composer
└── index.php        # Halaman utama aplikasi
```

## 👤 Author

**Raka Putra AL** - [GitHub](https://github.com/RakaPutraAL)

---

⭐ Jangan lupa kasih star kalau projek ini bermanfaat!
