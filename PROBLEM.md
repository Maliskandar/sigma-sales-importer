# PROBLEM.md Kendala Teknis & Solusi

Dokumen ini merangkum permasalahan yang muncul saat membangun **Sigma Sales Importer**
beserta solusi yang diterapkan. Disusun mengikuti tabel "Potensi Masalah & Solusi"
pada Business Case.

---

## 1. Format 3 file input tidak konsisten

**Masalah.** Ketiga file punya susunan & nama kolom berbeda:

- `SALES DAILY` (sheet _Dailysales API_) 24 kolom, lengkap dengan `ADV`, `Warehouse`, `Status Order`.
- `SALES MP` (_Sheet1_) tanpa kolom `ADV`, alamat pakai `City` / `Province` (bukan `KabupatenCustomer` / `ProvinsiCustomer`).
- `SALES PRODUK` (_SALES ALL_) mirip daily tapi tanpa `Warehouse` / `Status Order`.

**Solusi.** Mapping kolom disimpan di tabel **`column_mappings`** (per `file_type`:
`daily` / `mp` / `produk`), bukan di-hardcode. Importer membaca header Excel lalu
menerjemahkannya ke kolom DB via tabel ini. Menambah/mengubah nama kolom cukup
mengubah data master tanpa menyentuh kode.

> File: `app/Services/ExcelImportService.php` (`mapRowData`), `app/Models/ColumnMapping.php`.

---

## 2. Nama Kanal di Excel berbeda dengan kode platform

**Masalah.** Input menulis `"Tiktok Shop"`, sedangkan platform di DB berkode `TIKTOK`.
Pencocokan `==` gagal dan platform tidak terdeteksi.

**Solusi.** Kolom **`platforms.aliases`** (JSON) menampung semua variasi penulisan
(`["TIKTOK SHOP","TIKTOK"]`). Method `Platform::resolveByKanal()` mencocokkan
case-insensitive terhadap `code` maupun `aliases`.

---

## 3. Produk bundling: 1 baris input → banyak baris output

**Masalah.** `BDL01` di input adalah 1 baris, tapi di output harus dipecah menjadi
2 baris komponen (**BOXL A** & **BOXL B**). Selain itu, **Omzet komponen berbeda antara
FINANCE dan MARKETING**:

| Komponen | Omzet FINANCE | Omzet MARKETING |    HPP |
| -------- | ------------: | --------------: | -----: |
| BOXL A   |       175.000 |         190.000 | 27.000 |
| BOXL B   |        93.000 |          90.000 | 22.500 |

(FINANCE memakai harga list per komponen; MARKETING memakai alokasi harga tagihan
yang totalnya = 280.000.)

**Solusi.** Tabel **`bundle_items`** menyimpan tiap komponen (`sku`, `name`,
`finance_price`, `marketing_price`, `hpp`, `sort_order`). `OutputGeneratorService`
memecah transaksi bundle menjadi beberapa baris dan memilih kolom harga sesuai
file tujuan. Kuantitas komponen = `qty_transaksi × qty_komponen`.

---

## 4. Harga jual / HPP berbeda tiap platform

**Masalah.** `PR01` yang sama memiliki HPP **56.000 di WEB** namun **84.000 di SHOPEE**.

**Solusi.** Tabel **`product_prices`** (relasi `product` × `platform`) menyimpan
`selling_price` & `hpp` per platform. HPP di output diambil dari sini; bila tidak ada,
fallback ke `products.hpp`.

---

## 5. Kolom turunan: Admin, Region, Advertiser, Platform, Promo

**Masalah.** Banyak kolom output tidak ada langsung di input, melainkan hasil turunan:

- **Admin** (`Putri`, `HANDOKO`, `YAYA`) tergantung toko.
- **Nama Toko** hasil parsing kolom `Toko` (`"SHOPEE|raya"` → `RAYA`).
- **Advertiser** dari kolom `ADV`; bila kosong (file marketplace) pakai default toko (`ADV EMPAT`).
- **Region** normalisasi provinsi (`Jawa Timur/Barat/Banten` → `JAWA`).
- **Platform** label output (`A` → `WEB`, `Tiktok Shop` → `TIKTOK SHOP`).
- **Kode Promo / TaxName** segmen terakhir kolom `Note` (`"RN/CO/CODE"` → `CODE`).
- **Payment type** untuk WEB pakai `MetodeBayar`; untuk marketplace pakai label platform.

**Solusi.** Master tambahan: **`stores`** (kode → admin + advertiser default) dan
**`regions`** (provinsi → region). Parsing `Toko` & `Note` dilakukan di
`OutputGeneratorService`. Semua aturan berbasis DB, mudah diperluas.

---

## 6. [BUG DITEMUKAN & DIPERBAIKI] Interpolasi string dengan aritmatika

**Masalah.** Kode importer memuat:

```php
"... ({$worksheet->getHighestRow() - 1} baris)"
```

PHP **tidak mengizinkan operasi aritmatika di dalam `{$...}`** → `ParseError`,
sehingga proses import gagal total sebelum sempat berjalan.

**Solusi.** Hitung dulu ke variabel:

```php
$dataRowCount = $worksheet->getHighestRow() - 1;
"... ({$dataRowCount} baris)"
```

---

## 6B. [BUG DITEMUKAN & DIPERBAIKI] Sel teks terbaca sebagai objek RichText

**Masalah.** PhpSpreadsheet mengembalikan sebagian sel teks sebagai objek
`RichText`, bukan `string`. Akibatnya:

- Pembersihan data di importer (`is_string()` → `trim`, ubah `''`/`'-'` menjadi `null`)
  dilewati karena nilainya objek, bukan string.
- Validasi kolom wajib memakai `empty()` dan `empty($object)` **selalu `false`**
  sehingga baris dengan **OrderNumber kosong pun lolos** dan tersimpan dengan nilai `''`.

**Solusi.** Saat membaca tiap sel, objek RichText dikonversi lebih dulu ke teks biasa:

```php
if ($cellValue instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
    $cellValue = $cellValue->getPlainText();
}
```

Setelah perbaikan, pembersihan data & validasi berjalan benar (OrderNumber kosong,
Date kosong, kode produk tak terdaftar, Quantity 0 semuanya tertangkap).

---

## 7. Duplikasi data saat re-import

**Masalah.** Meng-upload file yang sama dua kali berpotensi menggandakan baris.

**Solusi.** _Unique constraint_ `(order_number, product_code, file_source)` pada
`sales_transactions` + `updateOrCreate` (upsert) di `bulkUpsert()`. Re-import
memperbarui, bukan menduplikasi. Seluruh batch dibungkus `DB::transaction`.

---

## 8. File besar & memory limit

**Masalah.** File Excel besar bisa menghabiskan memori bila dibaca sekaligus.

**Solusi.** Baris diproses **per-chunk (100 baris)** dan disimpan bertahap. Proses
dijalankan **asynchronous via Queue** (`ImportExcelJob`), sehingga request HTTP tidak
menunggu dan tidak timeout.

---

## 9. Jumlah baris membengkak karena baris kosong berformat

**Masalah.** File Excel dari sumber sering menyisakan **baris kosong yang masih menyimpan
format** (border/warna) jauh di bawah data. Akibatnya `getHighestRow()` menganggap area
terpakai sampai ratusan baris mis. `SALES MP` terbaca **999 baris** padahal isinya 2,
dan `SALES PRODUK` terbaca **2 baris** padahal 1 (ada 1 baris kosong ekstra).

**Solusi.** Ditambahkan helper `countDataRows()` yang menghitung **hanya baris yang
benar-benar berisi data** (mengecek tiap sel dalam area data, melewati baris kosong).
Dipakai untuk `total_rows` maupun pesan log. Hasilnya akurat: DAILY 4, MP 2, PRODUK 1.

---

## Catatan / Batasan yang Diketahui

1. **Anomali pada contoh output FINANCE.** Pada order bundle `TES-21-6`, komponen
   **BOXL B** di contoh `FINANCE` tertulis Payment type = `Shopee`, padahal order
   tersebut kanal Tiktok dan di `MARKETING` kedua komponennya `Tiktok`. Aplikasi
   ini mengeluarkan nilai **konsisten `Tiktok`**. Hasil MARKETING 100% identik dengan
   contoh; FINANCE identik kecuali 1 sel ini.
2. **Notifikasi real-time** memakai **polling** (`setInterval` → endpoint progress),
   bukan WebSocket. Cukup untuk progress bar + toast; broadcasting (Reverb/Pusher)
   dapat ditambahkan bila diperlukan latensi lebih rendah.
3. **Harga komponen bundle** saat ini satu set (belum per-platform), karena data
   contoh hanya menampilkan bundle di satu platform (Tiktok). Skema mudah diperluas
   dengan tabel harga komponen per-platform seperti `product_prices`.
