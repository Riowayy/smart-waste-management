# Dokumentasi REST API EcoTrack

API ini mendukung aplikasi mobile dan menggunakan prefix versi `/api/v1`. Semua respons memakai JSON. Endpoint yang dilindungi memerlukan token Sanctum sebagai Bearer token.

## Autentikasi

Kirim token pada header setiap permintaan terproteksi:

```http
Authorization: Bearer 1|contoh-token-dari-respons-login
Accept: application/json
```

Token dibuat saat register atau login. Logout mencabut token yang sedang dipakai. Password dan hash password tidak pernah dimasukkan ke respons. Registrasi selalu menetapkan role `user` di server.

## Format respons

Respons berhasil menggunakan bentuk berikut:

```json
{
  "success": true,
  "message": "Permintaan berhasil.",
  "data": {}
}
```

Daftar berhalaman juga menyertakan objek `pagination` dengan `current_page`, `per_page`, `total`, dan `last_page`. Respons error menggunakan `success: false`, `message`, dan `data: null`. Error validasi menyertakan `errors` yang berisi daftar pesan per field.

## Endpoint

| Method | Endpoint | Token | Kegunaan |
|---|---|---:|---|
| POST | `/api/v1/auth/register` | Tidak | Membuat akun user dan token |
| POST | `/api/v1/auth/login` | Tidak | Login dan membuat token |
| POST | `/api/v1/auth/logout` | Ya | Mencabut token aktif |
| GET | `/api/v1/auth/me` | Ya | Mengambil profil user aktif |
| GET | `/api/v1/dashboard` | Ya | Statistik dan lima laporan terbaru milik user |
| GET | `/api/v1/categories` | Ya | Mengambil seluruh kategori |
| GET | `/api/v1/reports` | Ya | Mengambil laporan milik user dengan pagination/filter |
| POST | `/api/v1/reports` | Ya | Membuat laporan dan mengunggah foto |
| GET | `/api/v1/reports/{report}` | Ya | Mengambil detail laporan milik user |

Semua endpoint terproteksi hanya menerima akun ber-role `user`. Laporan dibatasi berdasarkan pemilik pada query database. User tidak dapat mengubah status melalui API mobile.

## Register

`POST /api/v1/auth/register` — `Content-Type: application/json`.

```json
{
  "name": "Ayu Lestari",
  "email": "ayu@example.com",
  "password": "password-kuat",
  "password_confirmation": "password-kuat"
}
```

`name` wajib, maksimal 255 karakter. `email` wajib, format email valid dan unik. Password wajib, panjang 8–255 karakter dan harus cocok dengan `password_confirmation`. Role tidak diterima dari request.

Respons berhasil: HTTP `201 Created`.

```json
{
  "success": true,
  "message": "Akun berhasil dibuat.",
  "data": {
    "user": { "id": 12, "name": "Ayu Lestari", "email": "ayu@example.com", "role": "user" },
    "token": "12|token-rahasia",
    "token_type": "Bearer"
  }
}
```

## Login

`POST /api/v1/auth/login` — `Content-Type: application/json`.

```json
{ "email": "ayu@example.com", "password": "password-kuat" }
```

Respons berhasil: HTTP `200 OK`, dengan `data.user`, `data.token`, dan `data.token_type`. Kredensial salah menghasilkan HTTP `401` dan pesan umum agar tidak membocorkan apakah email terdaftar. Akun admin ditolak dengan HTTP `403`. Endpoint register dan login dibatasi 10 permintaan per menit per kombinasi email dan alamat IP.

Contoh respons berhasil:

```json
{
  "success": true,
  "message": "Login berhasil.",
  "data": {
    "user": { "id": 12, "name": "Ayu Lestari", "email": "ayu@example.com", "role": "user" },
    "token": "12|token-rahasia",
    "token_type": "Bearer"
  }
}
```

## Logout

`POST /api/v1/auth/logout` — wajib menyertakan Bearer token.

Berhasil mencabut token yang mengautentikasi permintaan saat ini dan mengembalikan HTTP `200`.

```json
{ "success": true, "message": "Token aktif berhasil dicabut.", "data": null }
```

## Profil aktif

`GET /api/v1/auth/me` mengembalikan `id`, `name`, `email`, dan `role` dari akun pemilik token.

```json
{
  "success": true,
  "message": "Profil berhasil diambil.",
  "data": { "id": 12, "name": "Ayu Lestari", "email": "ayu@example.com", "role": "user" }
}
```

## Dashboard user

`GET /api/v1/dashboard` mengembalikan statistik database untuk laporan milik user aktif serta lima laporan terbaru. Semua nilai statistik mencakup `total`, `pending`, `in_progress`, `completed`, dan `rejected`. `data.recent_reports` memakai struktur laporan pada bagian Detail laporan.

Contoh:

```json
{
  "success": true,
  "message": "Dashboard berhasil diambil.",
  "data": {
    "statistics": { "total": 4, "pending": 1, "in_progress": 1, "completed": 1, "rejected": 1 },
    "recent_reports": []
  }
}
```

## Kategori

`GET /api/v1/categories` mengembalikan seluruh kategori dari database, diurutkan berdasarkan nama. Setiap item memiliki `id`, `name`, `slug`, dan `description`.

```json
{
  "success": true,
  "message": "Daftar kategori berhasil diambil.",
  "data": [
    { "id": 1, "name": "Organic Waste", "slug": "organic-waste", "description": null },
    { "id": 2, "name": "Plastic Waste", "slug": "plastic-waste", "description": null }
  ]
}
```

## Daftar laporan

`GET /api/v1/reports` hanya mengembalikan laporan milik user aktif.

Query parameter opsional:

| Parameter | Aturan | Contoh |
|---|---|---|
| `page` | Nomor halaman, default 1 | `?page=2` |
| `per_page` | Integer 1–50, default 15 | `?per_page=10` |
| `status` | `pending`, `in_progress`, `completed`, atau `rejected` | `?status=pending` |

Contoh respons: `data` adalah array laporan dan `pagination` berisi metadata halaman.

```json
{
  "success": true,
  "message": "Daftar laporan berhasil diambil.",
  "data": [
    {
      "id": 42,
      "category": { "id": 2, "name": "Plastic Waste", "slug": "plastic-waste", "description": null },
      "description": "Botol plastik ditemukan di taman",
      "image_url": "http://localhost/storage/waste-reports/contoh.jpg",
      "latitude": -6.2,
      "longitude": 106.816666,
      "status": "pending",
      "created_at": "2026-11-01T08:00:00.000000Z",
      "updated_at": "2026-11-01T08:00:00.000000Z"
    }
  ],
  "pagination": { "current_page": 1, "per_page": 15, "total": 1, "last_page": 1 }
}
```

## Buat laporan dan unggah foto

`POST /api/v1/reports` harus dikirim sebagai `multipart/form-data`. Nama field file adalah `image`.

| Field | Wajib | Aturan |
|---|---:|---|
| `description` | Ya | String, maksimal 10.000 karakter |
| `image` | Ya | JPEG, PNG, atau WebP; maksimal 5 MB |
| `category_id` | Tidak | ID kategori yang tersedia |
| `latitude` | Tidak | Angka dari -90 sampai 90 |
| `longitude` | Tidak | Angka dari -180 sampai 180 |

Contoh `curl`:

```bash
curl -X POST "http://localhost:8000/api/v1/reports" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 12|token-rahasia" \
  -F "description=Botol plastik ditemukan di taman" \
  -F "category_id=2" \
  -F "latitude=-6.200000" \
  -F "longitude=106.816666" \
  -F "image=@./botol.jpg;type=image/jpeg"
```

Server menetapkan pemilik dari token dan status awal `pending`; field pemilik maupun status dari klien tidak digunakan. File disimpan pada disk `public` di direktori `waste-reports`, dan respons berisi `image_url` yang dibangun dari konfigurasi URL aplikasi. Pastikan `public/storage` terhubung ke `storage/app/public` (`php artisan storage:link`) agar URL foto dapat dilayani.

Berhasil membuat laporan mengembalikan HTTP `201 Created`.

Respons create menggunakan struktur laporan yang sama seperti item daftar. `status` berisi `pending`, sedangkan `status_history` belum ada karena belum terjadi perubahan status.

## Detail laporan

`GET /api/v1/reports/{report}` mengembalikan detail hanya jika laporan dimiliki user aktif. Data memuat kategori, deskripsi, `image_url`, koordinat, status, waktu pembuatan/perubahan, serta `status_history`. Setiap riwayat memuat `old_status`, `new_status`, `note`, nama pelaku perubahan (bila masih tersedia), dan waktu perubahan. ID laporan milik pengguna lain menghasilkan HTTP `404`.

```json
{
  "success": true,
  "message": "Detail laporan berhasil diambil.",
  "data": {
    "id": 42,
    "category": { "id": 2, "name": "Plastic Waste", "slug": "plastic-waste", "description": null },
    "description": "Botol plastik ditemukan di taman",
    "image_url": "http://localhost/storage/waste-reports/contoh.jpg",
    "latitude": -6.2,
    "longitude": 106.816666,
    "status": "in_progress",
    "created_at": "2026-11-01T08:00:00.000000Z",
    "updated_at": "2026-11-01T09:00:00.000000Z",
    "status_history": [
      {
        "old_status": "pending",
        "new_status": "in_progress",
        "note": "Laporan sedang ditinjau.",
        "changed_by": "Admin EcoTrack",
        "created_at": "2026-11-01T09:00:00.000000Z"
      }
    ]
  }
}
```

## Error dan status HTTP

| HTTP | Makna |
|---:|---|
| 200 | Permintaan berhasil |
| 201 | Akun atau laporan berhasil dibuat |
| 401 | Token tidak ada/tidak valid atau kredensial login salah |
| 403 | Akun tidak memiliki izin |
| 404 | Resource tidak ditemukan atau bukan milik user |
| 422 | Validasi gagal; detail field ada pada `errors` |
| 429 | Batas permintaan autentikasi terlampaui |
| 500 | Kesalahan server dengan pesan umum; detail internal tidak dikirim ke klien |

Contoh error validasi:

```json
{
  "success": false,
  "message": "Data yang dikirim tidak valid.",
  "data": null,
  "errors": { "image": ["The image field must be an image."] }
}
```

## Menjalankan API secara lokal

1. Siapkan `.env` lokal dan database MySQL project menggunakan prosedur pengembangan yang sudah disepakati.
2. Jalankan migration pada database lokal yang memang disiapkan untuk project, termasuk migration Sanctum. Migration **tidak** dijalankan oleh implementasi ini.
3. Jalankan `php artisan storage:link` jika symlink storage belum ada.
4. Jalankan `php artisan serve --host=0.0.0.0` dan atur `APP_URL` ke alamat yang dapat dicapai perangkat mobile pada jaringan lokal. Jangan gunakan `localhost` dari ponsel; gunakan alamat LAN komputer.
5. Ambil token melalui register/login dan kirim melalui header Bearer.

Jangan pernah mengirim nilai secret dari `.env` kepada klien atau menambahkannya ke dokumentasi.
