# GoDocs

Aplikasi web berbasis Laravel untuk membuat dan mengelola dokumen secara online layaknya Google Docs.

## Fitur Saat Ini

- **Autentikasi:** Register, Login, Logout, Update Profil
- **Dashboard:** Manajemen dokumen pribadi dan dokumen yang dibagikan
- **Real-time Editor:**
  - Mengetik dokumen bersamaan (kolaborasi) secara *real-time*
  - Live cursor tracking (melihat posisi kursor teman)
  - Auto-save text secara otomatis tanpa lag
  - Pengaturan halaman (Zoom, Ukuran Kertas, Orientasi, Spasi)
- **Berbagi Akses:** Fitur berbagi dokumen ke email tertentu dengan opsi `Hanya Lihat` atau `Bisa Edit`
- **Riwayat Versi:** Simpan titik penting tulisan dan kembalikan (restore) kapan saja.

## Teknologi

- PHP 8.3 & Laravel 12
- MySQL Database
- Vanilla JS & CKEditor 5 (Custom implementation)
- CSS Native

## Cara Install

1. Clone repo ini
2. Copy `.env.example` jadi `.env`
3. Isi konfigurasi database di file `.env`
4. Jalankan perintah berikut:

```bash
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

5. Buka `http://localhost:8000` di browser
