# SweetBrew - Inventory & Demand Forecasting App

Aplikasi manajemen inventaris kedai kopi (*Coffee Shop*) berbasis peramalan kebutuhan stok menggunakan metode **Single Exponential Smoothing (SES)**, perhitungan **Safety Stock**, dan **Reorder Point (ROP)**.

---

## 🛠️ Prasyarat Sistem (*Prerequisites*)

Pastikan perangkat Anda sudah terinstal:
- **PHP** $\ge$ 8.2 (disarankan menggunakan Laragon / XAMPP)
- **Composer** (untuk dependensi backend Laravel)
- **Node.js** ($\ge$ v18) & **NPM** (untuk frontend Vue 3)
- **MySQL / MariaDB**

---

## 🚀 Panduan Instalasi (*Installation Guide*)

### 1. Clone Repository
```bash
git clone https://github.com/margaretanp/inventory-forecasting-app.git
cd inventory-forecasting-app
```

---

### 2. Setup Backend (Laravel)

1. **Masuk ke folder backend:**
   ```bash
   cd backend
   ```

2. **Install dependensi PHP:**
   ```bash
   composer install
   ```

3. **Salin file konfigurasi environment:**
   ```bash
   cp .env.example .env
   # Di Windows Command Prompt / PowerShell jika cp gagal:
   copy .env.example .env
   ```

4. **Konfigurasi Database di file `.env`:**
   Buka file `.env` di folder `backend`, lalu sesuaikan konfigurasi database:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=db_inventory_forecasting
   DB_USERNAME=root
   DB_PASSWORD=
   ```
   *(Pastikan database `db_inventory_forecasting` sudah dibuat di MySQL / phpMyAdmin).*

5. **Generate Application Key:**
   ```bash
   php artisan key:generate
   ```

6. **Jalankan Migrasi & Seeder Data:**
   ```bash
   php artisan migrate
   php artisan db:seed --class=CoffeeShopInventorySeeder
   ```

---

### 3. Setup Frontend (Vue 3 + Vite)

1. **Buka terminal baru, masuk ke folder frontend:**
   ```bash
   cd frontend
   ```

2. **Install dependensi Node.js:**
   ```bash
   npm install
   ```

---

## 💻 Cara Menjalankan & Mengakses Aplikasi

Jalankan kedua server berikut di terminal terpisah:

### 1. Jalankan Backend Server
Di dalam folder `backend`:
```bash
php artisan serve
```
Backend API akan berjalan di: **`http://127.0.0.1:8000`**

### 2. Jalankan Frontend Server
Di dalam folder `frontend`:
```bash
npm run dev
```
Frontend Vite akan berjalan di: **`http://localhost:5173`**

---

## 🌐 Akses Aplikasi

Buka browser Anda dan kunjungi:
👉 **[http://localhost:5173](http://localhost:5173)**

---

## 📌 Fitur Utama
- **Dashboard Interaktif**: Bento summary cards, metrik akurasi (MAE, MAPE), dan proyeksi periode berikutnya.
- **SES Demand Forecasting Chart**: Kurva visualisasi interaktif peramalan stok (Actual vs Forecast) dengan slider bobot Alpha ($\alpha$).
- **Restock Pipeline (Kanban Board)**: Manajemen alur pengadaan barang (*To Order* $\rightarrow$ *Purchased* $\rightarrow$ *Received* $\rightarrow$ *Stock Synced*).
- **Master Data**: Monitoring stok aktual, lead time, batas safety stock, dan titik pemesanan ulang (ROP).
- **Mutasi Stok (Stock Movements)**: Pencatatan barang masuk (*in*) dan barang keluar (*out*).
- **Import CSV**: Fitur upload berkas CSV inventaris langsung ke database.
