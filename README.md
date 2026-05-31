# GoDocs

Aplikasi web berbasis Laravel untuk membuat dan mengelola dokumen secara online layaknya Google Docs dengan alur kolaborasi teks polos yang ringan dan responsif.

## Fitur Saat Ini

- **Autentikasi:** Register, Login, Logout, Update Profil
- **Dashboard:** Manajemen dokumen pribadi dan dokumen yang dibagikan
- **Real-time Editor:**
  - Mengetik dokumen bersamaan (kolaborasi) secara *real-time* berbasis textarea yang ringan dan responsif
  - Live cursor tracking (melihat posisi kursor melayang pengguna lain secara dinamis)
  - Auto-save text secara otomatis dengan penanganan konflik pengetikan cerdas
  - Ekspor dokumen langsung ke format PDF dan file TXT
- **Berbagi Akses:** Fitur berbagi dokumen ke email tertentu dengan opsi `Hanya Lihat` atau `Bisa Edit`
- **Riwayat Versi:** Simpan titik penting tulisan dan kembalikan (restore) kapan saja.

## Teknologi

- PHP & Laravel
- MySQL Database
- Vanilla JS (JavaScript murni tanpa library eksternal yang berat)
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
