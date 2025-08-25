# 📈 Aplikasi Web Salesman

Aplikasi ini adalah sistem manajemen penjualan berbasis web yang dirancang untuk membantu salesman dalam mengelola data penjualan, pelanggan, dan aktivitas terkait lainnya.

## 📜 Tentang Proyek

Proyek ini dibangun untuk menyediakan platform terpusat bagi para salesman. Dengan menggunakan aplikasi ini, salesman dapat dengan mudah melacak target, mencatat transaksi, dan mengelola data pelanggan secara efisien. Aplikasi ini memiliki antarmuka yang modern dan interaktif berkat penggunaan DaisyUI dan SweetAlert2.

## ✨ Fitur Utama

- Manajemen Pelanggan: Menambah, melihat, dan mengubah data pelanggan.

- Pencatatan Penjualan: Mencatat transaksi penjualan baru.

- Laporan Penjualan: Melihat riwayat dan ringkasan penjualan.

- Dasbor Interaktif: Menampilkan data statistik penting secara visual.

## 🛠️ Teknologi yang Digunakan
Proyek ini dibangun dengan menggunakan teknologi modern, antara lain:

- Framework Backend: PHP dengan Laravel

- Framework CSS: Tailwind CSS

- Komponen UI: DaisyUI

- Notifikasi & Alert: SweetAlert2

- Frontend Bundler: Vite

- Dependency Manager: Composer & NPM

## 🚀 Panduan Instalasi
Ikuti langkah-langkah berikut untuk menjalankan proyek ini di lingkungan lokal Anda.

### Prasyarat
Pastikan Anda sudah menginstal perangkat lunak berikut di komputer Anda:

PHP (versi 8.1 atau lebih baru direkomendasikan)

Composer

Node.js & NPM

Web Server server bawaan Laravel

Database (misal: MySQL, MariaDB)

### Langkah-langkah Instalasi
#### 1. Clone repositori ini:
``` 
git clone https://github.com/Fdjri/web_project_salesman.git
cd web_project_salesman
```
#### 2. Instal dependensi PHP menggunakan Composer:
```
composer install
```
#### 3. Salin file .env.example menjadi .env:
```
cp .env.example .env
```
#### 4. Buat kunci aplikasi (Application Key):
```
php artisan key:generate
```
#### 5. Konfigurasi database Anda:
Buka file .env dan sesuaikan pengaturan database sesuai dengan konfigurasi lokal Anda.
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database_anda
DB_USERNAME=user_database_anda
DB_PASSWORD=password_anda
```
#### 6. Jalankan migrasi database:
Perintah ini akan membuat semua tabel yang diperlukan di database Anda.
```
php artisan migrate
```
_Opsional: Jika Anda memiliki seeder, jalankan juga perintah php artisan db:seed._

#### 7. Instal dependensi frontend menggunakan NPM:
```
npm install
```
#### 8. Jalankan Vite untuk kompilasi aset frontend:
Untuk lingkungan pengembangan:
```
npm run dev
```
Untuk production:
```
npm run build
```
#### 9. Jalankan server pengembangan Laravel:
```
php artisan serve
```
Aplikasi Anda sekarang akan berjalan di 
```
http://127.0.0.1:8000.
