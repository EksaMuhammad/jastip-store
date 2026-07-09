# JastipKuy - Platform Jasa Titip On-Demand

JastipKuy adalah platform jasa titip (jastip) on-demand berbasis wilayah.

## Persyaratan Sistem

- PHP >= 8.2
- Composer
- Node.js & NPM
- MySQL >= 8.0

## Cara Install

1. Clone repositori dan masuk ke direktori proyek.
2. Salin file `.env.example` menjadi `.env`:
   ```bash
   cp .env.example .env
   ```
3. Install dependensi PHP:
   ```bash
   composer install
   ```
4. Install dependensi Node:
   ```bash
   npm install
   ```
5. Generate application key:
   ```bash
   php artisan key:generate
   ```
6. Pastikan MySQL berjalan, lalu jalankan migrasi database beserta data awal (seeder):
   ```bash
   php artisan migrate --seed
   ```
7. Jalankan server local backend:
   ```bash
   php artisan serve
   ```
8. Jalankan server compile frontend (Vite):
   ```bash
   npm run dev
   ```

## Struktur Folder Proyek

- **`app/Models`**: Menyimpan 17 Eloquent Model utama (Customer, Jastiper, Admin, Order, dll.) lengkap dengan relasi dan trait `SoftDeletes`.
- **`app/Http/Controllers`**: Controller Laravel konvensional.
- **`app/Livewire`**: Komponen Livewire untuk tampilan UI yang dinamis dan reaktif.
- **`app/Services`**: Kelas layanan untuk logic bisnis kompleks (misalnya kalkulasi fare, split-bill, refund, pembagian komisi) guna menjaga controller tetap ramping.
- **`database/migrations`**: Migrasi untuk 17 tabel dengan relasi polymorphic dan composite unique constraints.
- **`database/seeders`**: Seeder data master awal (wilayah, cancellation policies, dll.).
- **`config/auth.php`**: Konfigurasi multi-guard authentication (`customer`, `jastiper`, `admin`).
