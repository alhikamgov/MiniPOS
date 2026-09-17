<div align="center">

# 🏪 MiniPOS

### Aplikasi Point of Sale (POS) Berbasis Web

**Untuk Minimarket, Toko Tanpa Cabang, dan Toko Kecil**

[![Status](https://img.shields.io/badge/status-stable-success)]()
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)]()
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white)]()
[![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.x-38B2AC?logo=tailwind-css&logoColor=white)]()
[![License](https://img.shields.io/badge/license-MIT-blue)]()

Aplikasi kasir modern, ringan, dan mudah digunakan untuk toko kecil. Kelola produk, transaksi, stok, dan laporan dalam satu aplikasi.

</div>

---

## 📖 Tentang MiniPOS

**MiniPOS** adalah aplikasi Point of Sale (POS) berbasis web yang dirancang khusus untuk:

- 🏪 **Minimarket**
- 🏬 **Toko tanpa cabang** (single-store)
- 🛒 **Toko kecil / warung / kelontong**

Dibangun dengan **PHP Native** dan **MySQL**, aplikasi ini ringan, cepat, dan tidak memerlukan server mahal. Cukup jalankan di PC/laptop toko dengan XAMPP atau Laragon.

### 🎯 Cocok untuk siapa?

- Pemilik toko yang ingin **beralih dari catatan manual ke digital**
- Toko dengan **1-3 kasir**
- Bisnis yang butuh **laporan penjualan & laba akurat**

---

## ✨ Fitur Utama

| Kategori | Fitur |
|----------|-------|
| 🔐 **Autentikasi** | Login multi-role (Admin & Kasir) dengan Bcrypt password hashing |
| 📦 **Manajemen Produk** | CRUD produk, kategori, supplier, upload foto, dan stok |
| 🛒 **Transaksi POS** | Barcode scanner, numpad, keranjang real-time, dan auto-hitung |
| 🏷️ **Diskon** | Diskon persen (%) atau nominal (Rp) per transaksi |
| 💰 **Laba per Transaksi** | Perhitungan otomatis harga beli vs harga jual |
| ⭐ **Loyalty Point** | Sistem poin pelanggan (Rp 1.000 = 1 poin) |
| 👥 **Multi User** | Kelola akun kasir dengan role terpisah |
| 📊 **Dashboard & Grafik** | Pie chart kategori, metode bayar, dan jam sibuk |
| 🧾 **Cetak Struk** | Format thermal 58mm siap cetak |
| 🌓 **Dark/Light Mode** | Tema dapat diubah oleh Admin |
| 📱 **Responsif** | Tampil optimal di desktop, tablet, dan HP |

---

## 👥 Role & Fitur Setiap Role

Sistem ini memiliki **2 role utama** dengan pembagian hak akses yang jelas.

### 🔴 1. Admin (Pemilik / Manajer)

Role ini memiliki **akses penuh** terhadap seluruh modul aplikasi.

#### Fitur Admin:

**📊 Dashboard**
- Ringkasan Total Produk
- Omzet Hari Ini
- Laba Hari Ini (harga jual − harga beli)
- Jumlah Stok Menipis
- Total Transaksi

**📦 Manajemen Produk**
- Tambah / edit / hapus produk
- Upload **foto produk** (hanya Admin yang bisa)
- Kelola **kategori** produk
- Kelola **supplier** (pemasok)
- Sesuaikan **stok** dengan tombol akumulasi (`+1`, `+5`, `+25`, `−1`, `−5`, `−25`)
- Pencarian produk otomatis (setelah 5 karakter)
- Pagination 15 produk per halaman

**👥 Manajemen Pengguna**
- Tambah / edit akun Kasir
- Aktifkan / nonaktifkan akun Kasir
- Reset password Kasir

**👨‍👩‍👧 Manajemen Pelanggan**
- CRUD data pelanggan
- Lihat **poin loyalty** pelanggan
- Lihat **riwayat poin** (earn/redeem/adjust)
- Sesuaikan poin manual

**🧾 Riwayat Transaksi**
- Lihat semua transaksi dari semua kasir
- Filter berdasarkan periode, kasir, dan metode bayar
- Detail per invoice + laba per item

**📈 Laporan**
- Filter periode: Harian, Mingguan, Bulanan, Custom Range
- Kartu ringkasan: Transaksi, Omzet, Laba, Produk Terjual
- **Pie Chart**: Penjualan per Kategori
- **Pie Chart**: Metode Pembayaran
- **Bar Chart**: Jam Sibuk (highlight jam puncak)
- Top 5 Produk Terlaris

**⚙️ Pengaturan**
- Nama Aplikasi (tampil di login, sidebar, struk)
- Alamat & Telepon Toko (tampil di struk)
- Tema (Terang / Gelap)

---

### 🔵 2. Kasir (Operator)

Role ini **dibatasi** pada transaksi penjualan dan laporan pribadi.

#### Fitur Kasir:

**📊 Dashboard Kasir**
- Omzet Hari Ini (shift pribadi)
- Laba Hari Ini
- Jumlah Transaksi
- Notifikasi Stok Menipis

**🛒 Kasir / Transaksi (POS)**
- Cari produk via **barcode scanner** atau ketik manual
- Tekan **Enter** = langsung tambah ke keranjang
- Filter produk berdasarkan kategori
- Ubah qty (tambah/kurang/hapus item)
- **Terapkan diskon** (persen atau nominal)
- Pilih pelanggan member (untuk dapat poin)
- Pilih metode bayar: **Tunai / QRIS / Transfer / Debit**
- **Numpad** untuk input uang tunai + tombol cepat (10rb, 20rb, 50rb, 100rb)
- Auto-hitung kembalian
- **Preview laba** sebelum bayar
- Cetak struk (thermal 58mm)
- **Mode Fullscreen** untuk fokus transaksi

**📋 Laporan Kasir**
- Daftar transaksi hari ini (shift pribadi)
- Detail per invoice
- Ringkasan omzet & laba hari ini

**⭐ Loyalty Point**
- Poin otomatis ditambahkan saat memilih pelanggan member
- Aturan: **Rp 1.000 = 1 poin**

> ⚠️ Kasir **tidak bisa** mengubah produk, harga, atau mengakses menu Admin.

---

## 🖼️ Tampilan Aplikasi

| Halaman | Deskripsi |
|---------|-----------|
| 🏠 **Login** | Halaman masuk dengan desain modern |
| 📊 **Dashboard** | Ringkasan performa toko |
| 🛒 **POS** | Halaman transaksi utama |
| 📦 **Produk** | Manajemen produk & stok |
| 📈 **Laporan** | Analisa penjualan dengan grafik |

*(Tambahkan screenshot di sini saat publish)*

---

## 🛠️ Teknologi yang Digunakan

| Layer | Teknologi |
|-------|-----------|
| **Backend** | PHP Native 8.0+ |
| **Database** | MySQL / MariaDB |
| **Frontend** | HTML5, TailwindCSS, Vanilla JavaScript |
| **Icons** | Font Awesome 6 |
| **Chart** | Chart.js |
| **Notifikasi** | SweetAlert2 |
| **Server** | Apache (XAMPP / Laragon) |

### 🧱 Arsitektur

- **PHP Native** tanpa framework — ringan & mudah dimodifikasi
- **PDO** dengan Prepared Statement (aman dari SQL Injection)
- **Bcrypt** untuk hashing password
- **Session-based authentication** dengan role guard
- **Modular include** (`header.php`, `footer.php`, `auth.php`)
- **AJAX** untuk operasi keranjang & pencarian tanpa reload

---

## 📝 Aturan Bisnis

| Aturan | Nilai |
|--------|-------|
| Konversi Poin Loyalty | Rp 1.000 = 1 poin |
| Format Invoice | `INV-YYYYMMDD-XXXX` |
| Diskon | Bisa % atau nominal |
| Laba | `(harga_jual − harga_beli) × qty` |
| Metode Bayar | Tunai, QRIS, Transfer, Debit |

---

## 🚧 Roadmap

- [x] Autentikasi multi-role
- [x] Manajemen produk + foto
- [x] Transaksi POS + barcode
- [x] Diskon & laba per transaksi
- [x] Loyalty point
- [x] Laporan dengan grafik
- [x] Cetak struk thermal
- [ ] Stok opname (opname fisik)
- [ ] Export laporan ke Excel/PDF
- [ ] Notifikasi stok via WhatsApp

---

## 📄 Lisensi

Proyek ini dilisensikan di bawah **MIT License** — bebas digunakan untuk keperluan pribadi maupun komersial.

---

## 👨‍💻 Developer

**Nama Anda**
- GitHub: [@alhikamgov](https://github.com/alhikamgov)
- Email: [alhikamadjid16@gmail.com]

---

## 🙏 Terima Kasih

Terima kasih kepada semua pihak yang telah mendukung pengembangan MiniPOS.

**Dibuat dengan ❤️ untuk UMKM Indonesia**

<div align="center">

⭐ Jika proyek ini bermanfaat, berikan **star** di GitHub! ⭐

</div>
