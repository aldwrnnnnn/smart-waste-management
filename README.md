# Smart Waste Management System

Sistem pelaporan dan pengelolaan sampah pintar terintegrasi berbasis arsitektur **Monorepo** yang menggabungkan **Website & REST API Backend (Laravel 12 + MySQL)**, **Layanan Deteksi Citra AI (Python + TensorFlow/Keras MobileNetV2 + FastAPI)**, dan **Aplikasi Mobile Pelapor (Flutter)**.

---

## Arsitektur & Struktur Monorepo

```text
smart-waste-management/
├── dataset/                    # Dataset citra pelatihan & pengujian (450 gambar: organic, paper, plastic)
│   ├── organic/                # 150 gambar Organic Waste
│   ├── paper/                  # 150 gambar Paper Waste
│   └── plastic/                # 150 gambar Plastic Waste
├── docs/                       # Dokumentasi teknis, evaluasi ML, Postman, & file APK
│   ├── ml_evaluation/          # Bukti evaluasi model (classification_report.txt, confusion_matrix.png, training_history.png)
│   ├── api_contract.md         # Spesifikasi endpoint REST API Laravel & FastAPI
│   ├── database_schema.md      # Diagram ERD (Mermaid) & DDL SQL tabel users dan waste_reports
│   ├── dataset_info.md         # Dokumentasi sumber dataset, split 80:20, & augmentasi
│   ├── postman_collection.json # Koleksi pengujian API siap impor ke Postman
│   └── smart-waste-mobile.apk  # File instalasi aplikasi Android (Release APK)
├── ml-service/                 # Layanan Machine Learning & AI Prediction API (FastAPI)
│   ├── main.py                 # Server REST API FastAPI (GET /health, POST /predict)
│   ├── train_model.py          # Script pelatihan Transfer Learning MobileNetV2
│   ├── training_notebook.ipynb # Jupyter Notebook pelatihan & evaluasi model
│   ├── waste_classifier_model.keras # Bobot model terlatih (Test Accuracy: 94.44%)
│   ├── class_indices.json      # Pemetaan indeks kelas model
│   └── requirements.txt        # Daftar pustaka Python
├── mobile-app/                 # Aplikasi Mobile Warga Pelapor (Flutter)
│   ├── android/                # Konfigurasi Android & AndroidManifest.xml (Camera, GPS, Internet)
│   ├── lib/                    # Kode sumber Dart (config, models, services, screens, theme, widgets)
│   ├── test/                   # Pengujian unit & widget Flutter
│   └── pubspec.yaml            # Konfigurasi paket Flutter
├── web-backend/                # Website Operasional & Main Backend REST API (Laravel 12)
│   ├── app/                    # Controllers (Web & API), Middleware Role, Models (User, WasteReport)
│   ├── database/               # Migrations & DatabaseSeeder (Admin, User, 15 laporan awal)
│   ├── resources/views/        # Antarmuka Blade + Tailwind CSS + Chart.js
│   ├── routes/                 # Definisi rute web.php & api.php (Laravel Sanctum)
│   ├── tests/Feature/          # Pengujian otomatis Web & REST API
│   └── .env.example            # Template konfigurasi environment Laravel
└── .gitignore                  # Aturan keamanan berkas Git Monorepo
```

---

## Fitur Utama Sistem

1. **Machine Learning & FastAPI (`ml-service/`)**
   - Klasifikasi otomatis 3 kategori sampah (`Organic Waste`, `Plastic Waste`, `Paper Waste`) menggunakan **Transfer Learning MobileNetV2**.
   - Akurasi pada data uji mencapai **94.44%** (450 gambar total, rasio 80:20).
   - Endpoint inferensi cepat `POST /predict` dengan dukungan `CORSMiddleware` dan pengecekan kesehatan `GET /health`.

2. **Website Admin & Portal Pelapor (`web-backend/`)**
   - Autentikasi berbasis peran (`admin` dan `user`) yang diproteksi oleh `RoleMiddleware`.
   - **Dashboard Statistik Visual:** Kartu metrik ringkasan status (`Total`, `Pending`, `In Progress`, `Completed`, `Rejected`) serta grafik interaktif **Chart.js** untuk perbandingan kategori sampah dan proporsi status.
   - **Manajemen Laporan (CRUD):** Unggah foto bukti, deteksi kategori AI otomatis langsung dari form web (dengan *fallback* manual apabila layanan AI sedang nonaktif), input koordinat GPS, pencarian kata kunci, serta filter kategori dan status.
   - **Tindak Lanjut Cepat Admin:** Pembaruan status laporan (`Pending`, `In Progress`, `Completed`, `Rejected`) beserta catatan petugas (`admin_notes`).
   - **Manajemen Pengguna (Khusus Admin):** CRUD akun pengguna dan pengaturan hak akses antara `admin` dan `user`.

3. **Aplikasi Mobile Pelapor (`mobile-app/`)**
   - Registrasi dan Login terhubung ke **Laravel Sanctum** (`SharedPreferences` Bearer Token).
   - **Dashboard Pengguna:** Ringkasan statistik status laporan pengguna dan daftar laporan terbaru.
   - **Buat Laporan + Deteksi AI + GPS:** Ambil foto dari Kamera atau Galeri (`image_picker`), deteksi otomatis kategori dan persentase keyakinan AI (`POST /api/reports/detect`), pengambilan koordinat GPS otomatis (`geolocator`), serta pengiriman laporan multipart (`POST /api/reports`).
   - **Riwayat & Detail Laporan:** Filter status laporan dengan badge warna berdedikasi dan tampilan detail beserta catatan tindak lanjut admin.

---

## Prasyarat Sistem

- **PHP** >= 8.2 & **Composer**
- **MySQL** >= 8.0 / MariaDB >= 10.6 (misalnya melalui Laragon / XAMPP)
- **Python** >= 3.10 (untuk `ml-service`)
- **Flutter SDK** >= 3.10 / Dart >= 3.0 (untuk `mobile-app`)

---

## Panduan Instalasi Langkah demi Langkah

### 1. Menjalankan Layanan AI FastAPI (`ml-service/`)

```powershell
cd ml-service

# Buat virtual environment Python
python -m venv .venv

# Aktifkan virtual environment (Windows PowerShell)
.\.venv\Scripts\Activate.ps1

# Instal seluruh dependensi
pip install -r requirements.txt

# (Opsional) Latih ulang model dari dataset/ jika diperlukan
# python train_model.py

# Jalankan server FastAPI pada port 8001
uvicorn main:app --host 127.0.0.1 --port 8001 --reload
```

Verifikasi bahwa layanan aktif melalui `http://127.0.0.1:8001/health` atau dokumentasi interaktif Swagger di `http://127.0.0.1:8001/docs`.

---

### 2. Menjalankan Website & Backend API Laravel (`web-backend/`)

Pastikan layanan **MySQL** aktif dan buat database kosong bernama `smart_waste_db` (atau biarkan migrasi Laravel membuatnya):

```powershell
cd web-backend

# Instal dependensi PHP
composer install

# Salin konfigurasi environment dan buat application key
Copy-Item .env.example .env
php artisan key:generate

# Sesuaikan kredensial MySQL di file .env jika diperlukan:
# DB_DATABASE=smart_waste_db
# DB_USERNAME=root
# DB_PASSWORD=
# ML_SERVICE_URL=http://127.0.0.1:8001

# Buat symbolic link penyimpanan foto publik
php artisan storage:link

# Jalankan migrasi tabel beserta data awal (Seeder)
php artisan migrate:fresh --seed

# Jalankan server Laravel pada port 8000 (gunakan --host=0.0.0.0 jika diakses dari HP fisik)
php artisan serve --host=0.0.0.0 --port=8000
```

Buka website di browser pada alamat `http://127.0.0.1:8000`.

#### Akun Demo Bawaan (Hasil Seeder)

| Peran (Role) | Email | Password | Hak Akses |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@waste.com` | `password` | Dashboard Admin, Semua Laporan, Ubah Status, Manajemen Pengguna |
| **User Pelapor 1** | `user@waste.com` | `password` | Dashboard Pelapor, Buat Laporan AI, Riwayat Laporan Pribadi (Web & Mobile) |
| **User Pelapor 2** | `siti@waste.com` | `password` | Dashboard Pelapor, Buat Laporan AI, Riwayat Laporan Pribadi (Web & Mobile) |

---

### 3. Menjalankan atau Menginstal Aplikasi Mobile Flutter (`mobile-app/`)

File APK siap pasang telah tersedia langsung pada `docs/smart-waste-mobile.apk`. Untuk menjalankan dari kode sumber:

```powershell
cd mobile-app

# Unduh paket dependensi Flutter
flutter pub get

# Atur URL API pada lib/config/api_config.dart:
# - Emulator Android (Default): http://10.0.2.2:8000/api
# - Perangkat HP Fisik (Wi-Fi yang sama): http://<IP_LOKAL_KOMPUTER>:8000/api

# Jalankan aplikasi pada emulator atau perangkat terhubung
flutter run

# Atau kompilasi ulang APK Release
flutter build apk --release
```

---

## Pengujian Otomatis (Testing & Static Analysis)

1. **Pengujian Backend & Website Laravel:**
   ```powershell
   cd web-backend
   php artisan test
   ```
2. **Analisis Statis & Widget Test Flutter:**
   ```powershell
   cd mobile-app
   flutter analyze
   flutter test
   ```
3. **Uji Inferensi Endpoint FastAPI:**
   ```powershell
   curl.exe -F "file=@dataset/plastic/plastic101_trashnet.jpg" http://127.0.0.1:8001/predict
   ```
