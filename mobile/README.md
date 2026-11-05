# EcoTrack Mobile

Aplikasi EcoTrack untuk Android, iOS, dan Web, dibangun dengan Expo SDK 58, React Native 0.88.1, React 19.3, TypeScript 6, dan Expo Router. Laravel adalah backend utama dan sumber data laporan.

## Fitur yang tersedia

- Registrasi, login, pemulihan sesi, logout, dan penyimpanan token aman melalui Expo SecureStore di Android/iOS.
- Dashboard statistik laporan dan daftar laporan terbaru.
- Form laporan dengan deskripsi, kategori, foto JPEG/PNG/WebP hingga 5 MB, dan lokasi GPS opsional dengan izin pengguna.
- Riwayat laporan dengan pencarian, pagination, filter status, foto, detail lokasi, dan riwayat perubahan status.
- Tampilan hasil prediksi AI dan confidence yang dikirim oleh API Laravel jika tersedia.
- Status laporan: Pending, In Progress, Completed, dan Rejected.

Fitur yang memerlukan backend bergantung pada ketersediaan API Laravel. Aplikasi mobile tidak memanggil FastAPI secara langsung.

## Persyaratan

- Node.js 22.13 atau lebih baru, sesuai persyaratan Expo SDK 58.
- npm dan perangkat/emulator Android atau iOS, atau browser untuk Expo Web.
- Backend Laravel aktif, database tersambung, dan dapat dijangkau dari perangkat yang menjalankan aplikasi.

## Instalasi dependency

```powershell
cd mobile
npm install
```

## Konfigurasi API

Salin `.env.example` menjadi `.env.local`, lalu atur URL API versi `/api/v1`:

```powershell
Copy-Item .env.example .env.local
```

Contoh untuk pengembangan di Android fisik:

```dotenv
EXPO_PUBLIC_API_URL=http://192.168.1.20:8000/api/v1
```

Ganti `192.168.1.20` dengan IP LAN komputer yang menjalankan Laravel. Ponsel dan komputer harus dapat berkomunikasi melalui jaringan yang sama; firewall perlu mengizinkan koneksi ke port backend. `localhost` pada ponsel menunjuk ke ponsel itu sendiri, bukan komputer. Android Emulator biasanya dapat memakai `http://10.0.2.2:8000/api/v1`; sesuaikan bila konfigurasi server menggunakan subdirektori. Gunakan HTTPS untuk server produksi.

`EXPO_PUBLIC_API_URL` adalah konfigurasi publik yang dimasukkan ke bundle, bukan tempat menyimpan secret. Setelah mengubah environment, muat ulang Expo. Backend Laravel harus aktif dan dapat dijangkau oleh perangkat untuk login, dashboard, kategori, laporan, foto, status, dan hasil AI.

Jalankan Laravel dari root repository dengan host jaringan, misalnya:

```powershell
php artisan serve --host=0.0.0.0 --port=8000
```

Kontrak endpoint dan contoh respons tersedia di [../docs/API.md](../docs/API.md).

## Menjalankan aplikasi

Dari direktori `mobile`:

```powershell
npx expo start
```

Perintah lain:

```powershell
npm run android
npm run ios
npm run web
```

Gunakan Expo Go atau development build yang kompatibel dengan SDK 58. Kamera, lokasi, dan perilaku native perlu diperiksa pada perangkat yang sesuai.

## Pengujian

Jalankan pemeriksaan yang tersedia dari direktori `mobile`:

```powershell
node --test tests/Unit/*.test.cjs
npx tsc --noEmit
npm run lint
npx expo-doctor
```

Test unit menggunakan tiruan untuk kontrak client dan tidak membuktikan koneksi ke backend hidup. Uji alur autentikasi, izin foto/lokasi, upload, serta riwayat dengan Laravel aktif pada perangkat target sebelum distribusi.

## Build APK dengan EAS Build

Proyek mobile saat ini **belum memiliki `eas.json`**, konfigurasi profil build, atau identitas proyek EAS yang terdaftar. `app.json` mengatur nama/slug dasar dan plugin Expo, tetapi belum cukup untuk menyatakan bahwa APK sudah pernah dibangun. Expo Application Services juga memerlukan akun Expo dan konfigurasi proyek sebelum build cloud pertama.

1. Dari `mobile`, autentikasi ke Expo dan buat konfigurasi build EAS:

   ```powershell
   npx eas-cli@latest login
   npx eas-cli@latest build:configure
   ```

   Perintah konfigurasi akan membuat `eas.json`; tinjau hasilnya. Tambahkan atau sesuaikan profil APK, misalnya:

   ```json
   {
     "build": {
       "preview": {
         "distribution": "internal",
         "android": { "buildType": "apk" }
       }
     }
   }
   ```

   Profil dan isi JSON di atas adalah contoh yang perlu dibuat; belum ada pada repository saat ini.

2. Pastikan environment build memasok `EXPO_PUBLIC_API_URL` dengan alamat backend yang dapat dijangkau dari perangkat tempat APK dipasang. Jangan gunakan `localhost` untuk APK di ponsel fisik.

3. Jalankan build cloud:

   ```powershell
   npx eas-cli@latest build --platform android --profile preview
   ```

   APK yang dihasilkan dapat diunduh dari halaman build EAS dan dipasang pada perangkat Android. Build dan konfigurasi EAS belum dijalankan atau diverifikasi dalam repository ini.

Panduan resmi: [konfigurasi `eas.json`](https://docs.expo.dev/build/eas-json/) dan [membuat APK](https://docs.expo.dev/build-reference/apk/).
