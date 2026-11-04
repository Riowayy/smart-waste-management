# EcoTrack Mobile

Aplikasi mobile EcoTrack menggunakan Expo SDK 58, React Native, TypeScript, dan Expo Router.

## Kondisi saat ini

Halaman awal EcoTrack tersedia pada `src/app/index.tsx`, dengan navigasi Stack pada `src/app/_layout.tsx`. Komponen demo, navigasi tab demo, dan script reset template sudah dihapus. Komponen tema, hooks, serta aset aplikasi yang direferensikan `app.json` dipertahankan.

Fondasi client API Laravel dan penyimpanan token sudah tersedia. Alur autentikasi, pelaporan, GPS, dan deteksi AI belum diimplementasikan pada mobile.

## Konfigurasi dan penggunaan API

Salin `.env.example` menjadi `.env.local`, lalu atur `EXPO_PUBLIC_API_URL` dengan URL lengkap sampai `/api/v1`. Contoh lokal: `http://localhost:8000/api/v1`. Untuk perangkat fisik, gunakan IP LAN komputer; untuk Android Emulator, host komputer biasanya `10.0.2.2`. URL dapat memuat subdirektori XAMPP sebelum `/api/v1`. Gunakan HTTPS pada production. Lakukan reload penuh setelah mengganti environment; nilai ini disisipkan Expo saat bundling dan bersifat publik, sehingga tidak boleh berisi secret.

```ts
import { apiClient, ApiError } from '@/services/api/client';
import { tokenStorage } from '@/services/token-storage';

// Setelah task autentikasi mendapatkan token asli dari Laravel:
// await tokenStorage.setToken(response.data.token);

const response = await apiClient.request<unknown>('/auth/me');
// response mempertahankan envelope success/message/data dan pagination opsional.
```

`apiClient.request<T>(path, options)` menerima path relatif seperti `/auth/login`, tanpa prefix `/api/v1`. Kirim query melalui `query`, JSON melalui `body`, dan metode melalui `method` (default GET). Gunakan `authenticated: false` untuk login/register agar token lama tidak dikirim. Request lain membaca token dari `tokenStorage` dan mengirim Bearer bila tersedia. Client tidak menyimpan token dari respons secara otomatis.

Unggahan memakai `body: formData` dan `method: 'POST'` ke `/reports`. Tambahkan foto dengan nama field `image`; gunakan File/Blob pada Web atau objek file React Native `{ uri, name, type }` pada Android/iOS. Client tidak menetapkan `Content-Type` untuk FormData agar fetch membuat multipart boundary. Pemilihan foto dan service laporan menyusul pada task pelaporan; batas backend tetap JPEG/PNG/WebP 5 MB, deskripsi 10.000 karakter, kategori/koordinat opsional.

Error HTTP berupa `ApiError` dengan `kind: 'http'`, `status`, `message`, dan `errors` per field pada 422. Error transport dibedakan menjadi `network`, `timeout`, dan `cancelled`; JSON/envelope sukses yang tidak sesuai kontrak menghasilkan `invalid-response`. Timeout default 30 detik dapat diubah dengan `timeoutMs`; pembatalan memakai `signal` dari AbortController. Tidak ada retry otomatis untuk mencegah duplikasi request tulis. Tipe generik `T` perlu ditentukan service fitur sesuai kontrak Laravel; client memvalidasi envelope, bukan seluruh field domain.

`tokenStorage` menyediakan `getToken`, `setToken`, dan `clearToken`. Android/iOS memakai Expo SecureStore; kegagalan penyimpanan diteruskan ke caller. Web hanya memakai memori browser, sehingga reload atau penutupan halaman menghapus sesi lokal; tidak memakai localStorage/sessionStorage/AsyncStorage. SSR tidak dapat menyimpan atau membaca token browser. Penghapusan token lokal, respons 401, serta pencabutan token lewat logout perlu diatur task session management; client fondasi tidak melakukan navigasi atau logout otomatis.

Backend harus dapat dijangkau dari perangkat dan mengizinkan origin Web melalui CORS. URL foto mengikuti konfigurasi Laravel. Konfigurasi backend, kebijakan HTTP native, CORS, dan koneksi nyata perangkat belum diubah atau divalidasi oleh task ini.

## Pengembangan

- `npm start`: menjalankan Expo.
- `npm run web`: menjalankan pratinjau Web.
- `npx tsc --noEmit`: memeriksa TypeScript.
- `node --test tests/Unit/api-foundation.test.cjs`: menguji kontrak client/error/token dengan transport dan SecureStore tiruan, tanpa database.
- `npx expo install --check`: memeriksa kompatibilitas dependency SDK.
- `npx expo-doctor`: memeriksa kesehatan konfigurasi Expo.
- `npx expo export --platform web`: menghasilkan build Web ke `dist/`.
- `git diff --check`: memeriksa whitespace perubahan.

Script `npm run lint` tersedia, tetapi ESLint dan konfigurasinya belum disiapkan. Jangan menjalankannya sebagai pemeriksaan baca-saja: Expo dapat memasang dependency secara otomatis.

Konfigurasi Web memakai `output: "server"`; deployment membutuhkan runtime server yang sesuai.

## Konteks proyek

Baca `AGENTS.md`, `../AGENTS.md`, dan seluruh `instrucktor/` sebelum mengubah kode. Kontrak backend berada di `../docs/API.md`. Dependency dan versi stack dipertahankan selama pembersihan template.

## Autentikasi (TASK 002)

Halaman /login dan /register tersedia. AuthProvider/useAuth memulihkan sesi dengan /auth/me dan melindungi index menggunakan Stack.Protected. Formulir memakai client TASK 001. Index menampilkan dashboard TASK 003; index dan reports dilindungi Stack.Protected.

Pemulihan sesi yang gagal karena jaringan dapat dicoba kembali; token invalid dibersihkan. Logout yang gagal karena jaringan mempertahankan sesi sampai pencabutan token berhasil atau server menyatakan token sudah invalid. Web tetap memakai token di memori (reload mengakhiri sesi lokal). TASK 002.1 telah memverifikasi API nyata: login 200, auth/me 200, logout 200, dan auth/me dengan token yang dicabut 401. Pengujian perangkat tetap terpisah.

Jalankan seluruh unit test dari mobile: `node --test tests/Unit/*.test.cjs`.

## Dashboard (TASK 003)

Dashboard memakai GET /api/v1/dashboard melalui client terpusat untuk statistik dan maksimal lima laporan terbaru. Tersedia identitas pengguna, kategori nullable, tanggal Indonesia, status, loading, retry, empty state, tombol perbarui, dan pull-to-refresh. Data sebelumnya tetap tampil jika refresh gagal; respons 401/403 memicu pemeriksaan ulang sesi. Request dibatalkan saat refresh ulang atau unmount.

Navigasi /reports sudah terlindungi, tetapi hanya halaman tujuan sementara; riwayat lengkap belum diimplementasikan. Tidak ada dependency baru atau perubahan backend.

## Pelaporan sampah (TASK 004)

Buka **Buat laporan sampah** dari dashboard. Form menyediakan deskripsi wajib (maksimal 10.000 karakter), kategori opsional dari API, foto wajib JPEG/PNG/WebP maksimal 5 MB, serta lokasi opsional dengan izin foreground. Foto dapat dipreview, diganti, dan dihapus; lokasi dapat dihapus sebelum pengiriman.

POST reports memakai multipart dan Bearer dari client existing. Web mengirim File; Android/iOS descriptor URI. Setelah respons berhasil, layar konfirmasi menyediakan tombol kembali ke dashboard; dashboard mengambil data baru setiap mendapat fokus. Submit bersamaan dikunci. Respons jaringan/timeout yang tidak pasti meminta pengguna memeriksa dashboard, tanpa retry otomatis; backend belum menyediakan idempotency key.

Dependency Expo ditambah melalui expo install: expo-image-picker dan expo-location. Plugin mengatur teks izin kamera/foto/lokasi, tanpa izin mikrofon dan tanpa pelacakan lokasi background. Kamera Web tergantung dukungan browser/perangkat; lokasi Web memerlukan secure context (HTTPS atau localhost). Build native baru diperlukan untuk perubahan plugin izin. Uji kamera/GPS dan upload pada perangkat nyata sebelum rilis.
