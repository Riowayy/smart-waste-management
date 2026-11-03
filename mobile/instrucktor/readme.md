# EcoTrack Mobile - Project Context & Development Guide

**Repository:** Riowayy/smart-waste-management

**Branch:** main

**Tanggal baseline:** 3 November 2026

## 1. Project Overview

EcoTrack merupakan sistem manajemen sampah yang memungkinkan pengguna melaporkan temuan sampah melalui aplikasi dan memantau perkembangan laporan.

Sistem memiliki dua jenis pengguna:

1. Admin: mengelola pengguna, kategori, laporan, dan perubahan status.
2. User: mengirim laporan sampah dan memantau status laporan.

Aplikasi mobile dikembangkan menggunakan Expo dan React Native, sedangkan backend menggunakan Laravel REST API.

## 2. Existing Technology Stack

| Komponen | Teknologi |
|---|---|
| Backend | Laravel 13 |
| Database | MySQL |
| Authentication | Laravel Sanctum |
| Mobile | Expo |
| Framework | React Native |
| Language | TypeScript |
| Navigation | Expo Router |
| API | REST API |
| Styling | React Native StyleSheet |

Versi dependency mobile pada pemeriksaan repository:
- Expo ~58.0.22
- Expo Router ~58.0.29
- React 19.3.0
- React Native 0.88.1
- TypeScript ~6.0.3

Verifikasi ulang versi aktual sebelum menambahkan dependency.

## 3. Existing Mobile Structure

Struktur mobile saat ini:

```text
mobile/
├── assets/
├── src/
│   ├── app/
│   │   ├── _layout.tsx
│   │   └── index.tsx
│   ├── components/
│   ├── constants/
│   ├── hooks/
│   └── global.css
├── app.json
├── package.json
└── tsconfig.json
```

Kondisi saat ini:

- Proyek Expo sudah tersedia.
- Expo Router sudah dikonfigurasi.
- TypeScript strict mode sudah aktif.
- Alias `@/*` sudah tersedia.
- Halaman awal EcoTrack tersedia dengan navigasi Stack; halaman Explore, navigasi tab demo, animasi, dan aset demo sudah dihapus.
- Belum tersedia autentikasi mobile.
- Fondasi API client mobile tersedia di `src/services/api/`; token di `src/services/token-storage.ts` (TASK 001).
- Belum tersedia dashboard EcoTrack mobile.
- Belum tersedia form laporan sampah.
- Belum tersedia integrasi kamera dan GPS.

Halaman awal ini merupakan fondasi tampilan; fitur bisnis mobile belum diimplementasikan. Aset icon, splash, favicon, dan adaptive icon tetap dipertahankan sesuai app.json. Override ios.icon sudah dilepas sehingga iOS menggunakan icon.png utama; folder assets/expo.icon dan tabIcons kosong sudah dihapus. Enam PNG yang tersisa masih aset starter dan perlu diganti dengan desain EcoTrack sebelum rilis; belum ada gambar pengganti yang dibuat.

## 4. Backend API Architecture

Backend menyediakan REST API dengan prefix:

`/api/v1`

Autentikasi menggunakan Laravel Sanctum.

Header autentikasi:

```http
Authorization: Bearer TOKEN
Accept: application/json
```

### Authentication Endpoints

| Method | Endpoint | Fungsi |
|---|---|---|
| POST | /auth/register | Registrasi pengguna |
| POST | /auth/login | Login |
| POST | /auth/logout | Logout |
| GET | /auth/me | Profil pengguna |

### Dashboard

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | /dashboard | Statistik dan laporan terbaru |

### Category

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | /categories | Daftar kategori sampah |

### Waste Reports

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | /reports | Daftar laporan |
| POST | /reports | Membuat laporan |
| GET | /reports/{report} | Detail laporan |

Seluruh endpoint yang dilindungi hanya menerima akun dengan role `user`.

## 5. Authentication Flow

Alur autentikasi yang harus diterapkan:

1. Pengguna membuka aplikasi.
2. Aplikasi memeriksa token yang tersimpan secara aman.
3. Jika token tersedia, aplikasi memanggil `/auth/me`.
4. Jika token valid, pengguna diarahkan ke dashboard.
5. Jika token tidak valid, pengguna diarahkan ke login.
6. Pengguna dapat melakukan registrasi.
7. Setelah login berhasil, token disimpan menggunakan secure storage.
8. Ketika logout, token aktif dicabut melalui API dan sesi lokal dihapus.

Jangan menggunakan AsyncStorage untuk menyimpan token autentikasi.

## 6. Waste Category

Kategori yang tersedia pada backend:

- Organic Waste
- Plastic Waste
- Paper Waste

Kategori harus diambil dari database melalui API.

Jangan menetapkan ID kategori secara permanen di aplikasi mobile.

## 7. Waste Report System

Laporan sampah memiliki data:

- id
- user_id
- category_id
- ai_category_id
- description
- image_path
- latitude
- longitude
- status
- ai_confidence
- created_at
- updated_at

Status laporan:

| Status | Keterangan |
|---|---|
| pending | Menunggu pemeriksaan |
| in_progress | Sedang diproses |
| completed | Selesai |
| rejected | Ditolak |

Status ditetapkan dan diperbarui melalui backend.

Pengguna mobile hanya dapat mengirim laporan dan melihat perkembangannya.

## 8. Create Waste Report

Endpoint:

`POST /api/v1/reports`

Gunakan multipart/form-data.

Field yang dikirim:

| Field | Keterangan |
|---|---|
| description | Deskripsi sampah |
| image | Foto sampah |
| category_id | Kategori, opsional |
| latitude | Koordinat latitude, opsional |
| longitude | Koordinat longitude, opsional |

Ketentuan upload:
- Format JPEG, PNG, atau WebP.
- Maksimal 5 MB.
- Deskripsi maksimal 10.000 karakter.

Backend menentukan pemilik laporan berdasarkan token dan menetapkan status awal `pending`.

## 9. Mobile Application Architecture

Arah struktur pengembangan:

```text
src/
├── app/
│   ├── (auth)/
│   │   ├── login.tsx
│   │   └── register.tsx
│   └── (app)/
│       ├── index.tsx
│       ├── reports/
│       │   ├── index.tsx
│       │   ├── create.tsx
│       │   └── [id].tsx
│       └── profile.tsx
├── components/
├── features/
│   ├── auth/
│   ├── dashboard/
│   └── reports/
├── services/
│   └── api/
├── constants/
├── hooks/
└── types/
```

Struktur ini merupakan rancangan pengembangan, bukan struktur yang sudah tersedia. Sesuaikan dengan implementasi Expo Router yang berjalan.

## 10. UI/UX Direction

Aplikasi harus memiliki karakter visual yang konsisten dengan identitas EcoTrack.

Arah desain:
- Modern.
- Minimalis.
- Profesional.
- Bersih dan mudah digunakan.
- Dominasi warna hijau sebagai identitas lingkungan.
- Navigasi sederhana.
- Komponen konsisten.

Website EcoTrack menggunakan warna utama emerald dengan referensi token `#006c49`.

Gunakan warna tersebut sebagai referensi awal dan selaraskan dengan keputusan desain mobile.

Halaman yang direncanakan:

1. Splash Screen.
2. Login.
3. Register.
4. Dashboard.
5. Daftar laporan.
6. Buat laporan.
7. Detail laporan.
8. Profil pengguna.

## 11. AI Integration

Database backend telah memiliki kolom:

- ai_category_id
- ai_confidence

Namun, endpoint prediksi AI untuk mobile belum ditemukan dalam source yang diperiksa.

Karena itu:

- Jangan membuat prediksi palsu.
- Jangan membuat hasil klasifikasi statis seolah-olah berasal dari model AI.
- Jangan menganggap kolom database sebagai bukti bahwa layanan ML sudah terintegrasi.
- Tunggu kontrak REST API ML yang sebenarnya.
- Pisahkan integrasi AI dari business logic utama aplikasi.

## 12. Security Requirements

Perhatikan hal berikut:

1. Gunakan secure token storage.
2. Jangan menyimpan credential backend dalam aplikasi.
3. Jangan mencatat token pada console.
4. Validasi input sebelum mengirim request.
5. Gunakan permission kamera dan lokasi secara tepat.
6. Jangan mengizinkan User mengubah status laporan.
7. Jangan mengakses laporan pengguna lain.
8. Tangani error API secara konsisten.

Catatan audit: konfigurasi Sanctum yang diperiksa belum menetapkan expiration token global. Selain itu, URL foto laporan berasal dari public storage. Kedua hal tersebut perlu ditinjau bersama backend sebelum deployment production.

## 13. Development Roadmap

Urutan pengerjaan:

**Phase 1 - Foundation**
- Persiapan struktur mobile.
- Konfigurasi API base URL.
- API client.
- Secure token storage.

**Phase 2 - Authentication**
- Login.
- Register.
- Logout.
- Session management.

**Phase 3 - Dashboard**
- Statistik laporan.
- Laporan terbaru.
- Kategori sampah.

**Phase 4 - Reporting**
- Form laporan.
- Upload foto.
- Kamera atau galeri.
- Lokasi GPS.
- Validasi laporan.

**Phase 5 - Report Management**
- Daftar laporan.
- Filter status.
- Pagination.
- Detail laporan.
- Riwayat status.

**Phase 6 - AI Integration**
- Integrasi REST API ML setelah kontrak tersedia.
- Menampilkan hasil prediksi aktual.
- Penanganan kegagalan layanan.

**Phase 7 - Testing**
- Authentication testing.
- API integration testing.
- Upload testing.
- Navigation testing.
- UI responsiveness.
- Security testing.

## 14. Development Rules

- Selalu periksa source sebelum mengubah kode.
- Jangan membuat dependency tanpa kebutuhan.
- Jangan menghapus file yang masih digunakan.
- Jangan mengubah backend tanpa instruksi.
- Jangan mengubah database production.
- Jangan membuat data dummy sebagai data aktual.
- Gunakan TypeScript.
- Gunakan reusable components.
- Gunakan Bahasa Indonesia pada UI.
- Jalankan lint dan TypeScript check setelah perubahan.
- Dokumentasikan perubahan arsitektur.

## 15. Important References

Repository:

https://github.com/Riowayy/smart-waste-management

Backend API documentation:

`../docs/API.md`

Main backend routes:

`../routes/api.php`

Mobile package:

`mobile/package.json`

Mobile configuration:

`mobile/app.json`

TypeScript configuration:

`mobile/tsconfig.json`

Dokumen ini menjadi konteks kerja Codex untuk pengembangan EcoTrack Mobile. Selalu verifikasi source terbaru sebelum implementasi.
