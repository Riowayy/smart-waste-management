# EcoTrack Smart Waste Management

EcoTrack adalah aplikasi pengelolaan laporan sampah untuk asesmen Full Stack Developer. Admin mengelola laporan, kategori, dan pengguna melalui aplikasi web; pengguna dapat membuat serta melacak laporan melalui web atau aplikasi mobile. Saat layanan tersedia, model klasifikasi gambar memberikan prediksi kategori sebagai informasi tambahan.

## Fitur

### Admin
- Dashboard ringkasan dan pengelolaan laporan sampah.
- Mengubah status laporan menjadi **Pending**, **In Progress**, **Completed**, atau **Rejected**, dengan catatan riwayat.
- Mengelola kategori dan akun pengguna.
- Melihat foto, lokasi, kategori pilihan pengguna, dan hasil prediksi AI pada laporan.

### User
- Membuat laporan melalui web dengan deskripsi, kategori, foto, dan lokasi.
- Melihat dashboard, riwayat, detail, serta perubahan status laporan.
- Menggunakan kategori **Organic Waste**, **Plastic Waste**, dan **Paper Waste**.

### Mobile
- Aplikasi Expo dengan autentikasi, dashboard, pembuatan laporan, foto, lokasi GPS, riwayat, filter status, dan detail laporan.
- Menampilkan kategori dan confidence prediksi AI yang dikirim melalui API Laravel.
- Menggunakan Laravel Sanctum untuk autentikasi API.

### AI
- FastAPI menyediakan klasifikasi tiga kelas menggunakan MobileNetV2 hasil transfer learning.
- Saat laporan dibuat, Laravel dapat mengirim foto ke FastAPI. Prediksi bersifat opsional: jika layanan tidak tersedia atau respons tidak valid, laporan tetap disimpan tanpa nilai AI.
- Mobile berkomunikasi dengan API Laravel, bukan langsung dengan FastAPI.

## Teknologi

| Bagian | Teknologi |
|---|---|
| Web dan backend | PHP 8.3+, Laravel 13, Blade, Laravel Sanctum |
| Styling dan aset web | Tailwind CSS 4, Vite 8 |
| Database | MySQL |
| Mobile | Expo SDK 58, React Native 0.88, React 19.3, TypeScript 6, Expo Router |
| ML API | Python 3.11, FastAPI, Uvicorn |
| Model | TensorFlow/Keras, MobileNetV2, scikit-learn |

## Struktur proyek

```text
app/                 Controller, model, layanan, dan logika Laravel
bootstrap/           Bootstrap aplikasi Laravel
config/              Konfigurasi Laravel, termasuk integrasi ML
database/            Migration, factory, dan seeder
docs/API.md           Kontrak REST API mobile
ml-service/           FastAPI, pipeline dataset/training, model dan evaluasi
mobile/               Aplikasi Expo, layar, fitur, dan layanan API
resources/views/      Tampilan Blade
routes/               Route web dan API
tests/                Test Laravel
README.md             Panduan utama
```

Dataset RealWaste tersedia pada lingkungan kerja lokal, tetapi tidak disertakan dalam Git.

## Persyaratan sistem

- Windows 11 dengan XAMPP atau lingkungan setara.
- PHP 8.3 atau lebih baru dan Composer.
- MySQL aktif dan database proyek yang sudah dibuat.
- Node.js 22.13 atau lebih baru dan npm.
- Python 3.11 untuk layanan ML.
- Koneksi internet diperlukan saat memasang dependency, mengunduh dataset, atau mengambil bobot ImageNet pada training pertama.

## Instalasi Laravel dan MySQL

Jalankan perintah dari root repository. Buat database MySQL terlebih dahulu melalui MySQL/XAMPP, lalu salin dan sesuaikan konfigurasi lokal:

```powershell
Copy-Item .env.example .env
composer install
```

Atur nilai lokal berikut pada `.env` (gunakan kredensial MySQL yang berlaku di mesin Anda):

```dotenv
APP_NAME=EcoTrack
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecotrack
DB_USERNAME=root
DB_PASSWORD=
ML_SERVICE_URL=http://127.0.0.1:8001
```

Kemudian siapkan aplikasi dan skema:

```powershell
php artisan key:generate
php artisan migrate
php artisan db:seed --class=WasteCategorySeeder
php artisan storage:link
npm install
npm run build
```

`WasteCategorySeeder` mengisi tiga kategori bawaan. Perintah migration hanya ditujukan untuk database lokal proyek yang disiapkan; jangan gunakan perintah reset database. `DatabaseSeeder` tidak membuat akun demo admin dan user.

## Menjalankan layanan

Jalankan tiap proses pada terminal tersendiri.

**Laravel** — pastikan MySQL aktif dan `.env` telah diatur:

```powershell
php artisan serve --host=0.0.0.0 --port=8000
```

**FastAPI** — dari direktori `ml-service`, instal dependency ML/API terlebih dahulu dan jalankan API:

```powershell
cd ml-service
py -3.11 -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install -r requirements.txt
uvicorn api:app --host 0.0.0.0 --port 8001
```

Model dan `labels.json` harus tersedia di `ml-service/artifacts/` sebelum FastAPI dimulai. Endpoint status berada di `http://127.0.0.1:8001/health`; Swagger UI di `http://127.0.0.1:8001/docs`. Laravel membaca alamat layanan dari `ML_SERVICE_URL`.

**Mobile** — dari direktori `mobile`, atur `EXPO_PUBLIC_API_URL` pada `.env.local` ke alamat Laravel yang bisa dijangkau perangkat, lalu jalankan:

```powershell
cd mobile
npm install
npx expo start
```

Untuk ponsel fisik, gunakan IP LAN komputer, bukan `localhost`. Panduan mobile lengkap ada di [mobile/README.md](mobile/README.md).

## Akun demo assessment

Kredensial berikut telah diverifikasi terhadap database lokal assessment ini, termasuk role dan password yang cocok:

| Peran | Email | Password |
|---|---|---|
| Admin | `admin@ecotrack.com` | `admin123` |
| User | `user@ecotrack.com` | `user123` |

Akun demo tidak dibuat oleh seeder repository. Ketersediaannya pada database assessment saat ini tidak berarti akun tersebut otomatis tersedia pada database instalasi baru.

## Dokumentasi terkait

- [Kontrak REST API](docs/API.md)
- [Panduan aplikasi mobile](mobile/README.md)
- [Panduan layanan Machine Learning](ml-service/README.md)
- [Sumber dataset dan atribusi RealWaste](ml-service/DATA_SOURCE.md)

## Evaluasi Machine Learning

Artefak evaluasi lokal mencatat **akurasi 93,33% pada 135 gambar test** (45 gambar per kelas). Pembagian dari 900 gambar seimbang adalah 630 gambar training (210 per kelas), 135 validation (45 per kelas), dan 135 test (45 per kelas). Angka ini mengukur held-out test split tersebut; bukan jaminan untuk foto ponsel atau kondisi lapangan yang berbeda.

Model tersimpan di `ml-service/artifacts/ecotrack.keras`. Artefak pendukung meliputi `metrics.json`, `labels.json`, `classification_report.txt`, `confusion_matrix.png`, dan `training_history.json`. Rincian pipeline, reproduksi dataset, dan keterbatasan lisensi tersedia di [README ML](ml-service/README.md).

## Pengujian

Jalankan perintah dari direktori yang disebutkan:

```powershell
# Laravel — root repository
php artisan test

# Mobile — direktori mobile
cd mobile
node --test tests/Unit/*.test.cjs

# ML — direktori ml-service
cd ../ml-service
python -m unittest discover -s tests
```

Perintah tersebut menjelaskan cara menjalankan test yang tersedia; hasil test tidak dinyatakan di sini.

## Deployment

Repository ini tidak menyertakan konfigurasi atau URL deployment yang telah diverifikasi. Aplikasi didokumentasikan untuk dijalankan secara lokal; deployment web, FastAPI, database, dan distribusi mobile belum diklaim tersedia.
