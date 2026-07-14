# Sigma Sales Importer

Sistem fullstack untuk **mengimpor 3 file Excel data sales**, memprosesnya melalui
database (validasi, normalisasi, transformasi, pemecahan bundle, HPP per-platform),
dan **menghasilkan 2 file output otomatis**: **FINANCE** dan **MARKETING**.

Dibangun untuk Business Case Full-Stack Engineer PT Sigma Digital Nusantara.

🎥 **Video demo:** https://youtu.be/GB1ZLN5gz_8

---

## Tech Stack

| Layer    | Teknologi                       |
| -------- | ------------------------------- |
| Backend  | Laravel 13 (PHP 8.3)            |
| Database | MySQL / PostgreSQL / SQLite     |
| Frontend | Blade + Vanilla JS + Vite       |
| Queue    | Laravel Queue (database driver) |
| Excel    | PhpSpreadsheet                  |
| Storage  | Local (`storage/app/private`)   |

---

## Alur Sistem

```
Upload 3 Excel → Validasi per-baris (via DB) → Simpan ke DB (chunk + upsert)
     → Transformasi (mapping platform, admin, region, bundle, HPP per-platform)
     → Generate FINANCE.xlsx & MARKETING.xlsx → Notifikasi (progress bar + toast)
```

---

## Persyaratan

- PHP >= 8.2 dengan ekstensi: `pdo`, `mbstring`, `gd`, `zip`, `sqlite3`/`pdo_mysql`
- Composer
- Node.js >= 18 & npm

---

## Instalasi

```bash
# 1. Install dependency PHP & JS
composer install
npm install

# 2. Siapkan environment
cp .env.example .env
php artisan key:generate

# 3. Konfigurasi database di .env
#    Untuk MySQL:
#      DB_CONNECTION=mysql
#      DB_DATABASE=sigma_sales
#      DB_USERNAME=root
#      DB_PASSWORD=
#    (buat database "sigma_sales" terlebih dahulu)
#
#    Atau cara tercepat pakai SQLite:
#      DB_CONNECTION=sqlite
#      (lalu jalankan)  touch database/database.sqlite

# 4. Migrasi + seed master data (platform, produk, bundle, toko, region, mapping)
php artisan migrate:fresh --seed

# 5. Symlink storage
php artisan storage:link

# 6. Build asset frontend
npm run build     # atau: npm run dev (mode watch)
```

---

## Menjalankan Aplikasi

Buka **dua terminal**:

```bash
# Terminal 1 web server
php artisan serve
# → http://127.0.0.1:8000

# Terminal 2 worker antrian (memproses import & generate output)
php artisan queue:work
```

> Jika `QUEUE_CONNECTION=sync` di `.env`, import diproses langsung tanpa worker.
> Untuk pemrosesan asynchronous (disarankan), set `QUEUE_CONNECTION=database`
> dan jalankan `php artisan queue:work`.

---

## Cara Pakai

1. Buka **Upload**, tarik-lepas (drag & drop) ketiga file:
   `SALES DAILY.xlsx`, `SALES MP.xlsx`, `SALES PRODUK.xlsx`.
2. Sistem memvalidasi tiap baris dan menampilkan **progress bar real-time**.
3. Setelah selesai, buka **Output** untuk mengunduh **FINANCE** & **MARKETING**.
4. **History** menampilkan log lengkap; error dapat diunduh sebagai laporan.
5. **Dashboard** menampilkan ringkasan statistik hasil import.

File output hasil transformasi contoh tersedia di folder [`result/`](result/).

---

## Struktur Data (Relasi)

- **platforms** kanal penjualan + alias + label output + payment label.
- **products** produk; `is_bundle` menandai bundle.
- **bundle_items** komponen tiap bundle (SKU, nama, harga finance/marketing, HPP).
- **product_prices** harga jual & HPP **per platform** (produk × platform).
- **stores** kode toko → admin & advertiser default.
- **regions** normalisasi provinsi → region.
- **column_mappings** pemetaan nama kolom Excel → kolom DB, per tipe file.
- **uploads / upload_logs** batch upload & log proses.
- **sales_transactions** data mentah hasil import (unique per `order_number` + `product_code` + `file_source`).

Semua aturan transformasi dikendalikan lewat tabel master di atas bukan hardcode.

---

## Fitur

- ✅ Upload 3 file Excel sekaligus (drag & drop)
- ✅ Validasi otomatis per baris berbasis DB
- ✅ Progress bar real-time + toast sukses/gagal
- ✅ Generate 2 file output otomatis (FINANCE & MARKETING)
- ✅ History log lengkap + error report downloadable
- ✅ Dashboard ringkasan statistik
- ✅ Rollback data per batch (add-on)
- ✅ Bulk upsert + unique constraint (anti-duplikasi saat re-import)

---

## Dokumen Terkait

- [`PROBLEM.md`](PROBLEM.md) kendala teknis & solusi yang diterapkan.
- [`result/`](result/) 2 file output hasil transformasi.
- [`VIDEO DEMO.md`](VIDEO%20DEMO.md) tautan video demo (YouTube).
- 🎥 Video demo: https://youtu.be/GB1ZLN5gz_8
