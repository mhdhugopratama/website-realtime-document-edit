# GoDocs

Aplikasi web berbasis Laravel untuk membuat dan mengelola dokumen secara online.

## Teknologi

- PHP 8.3
- Laravel 12
- MySQL
- HTML, CSS, JavaScript

## Fitur yang sudah ada

- Register akun baru
- Login dan logout
- Halaman utama setelah login

## Cara Install

1. Clone repo ini
2. Copy `.env.example` jadi `.env`
3. Isi konfigurasi database di file `.env`
4. Jalankan perintah berikut:

```
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

5. Buka `http://localhost:8000` di browser

## Catatan

Project ini masih dalam tahap pengembangan.
