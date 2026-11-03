# EcoTrack Mobile

Aplikasi mobile EcoTrack menggunakan Expo SDK 58, React Native, TypeScript, dan Expo Router.

## Kondisi saat ini

Halaman awal EcoTrack tersedia pada `src/app/index.tsx`, dengan navigasi Stack pada `src/app/_layout.tsx`. Komponen demo, navigasi tab demo, dan script reset template sudah dihapus. Komponen tema, hooks, serta aset aplikasi yang direferensikan `app.json` dipertahankan.

Autentikasi, integrasi API Laravel, pelaporan, GPS, dan deteksi AI belum diimplementasikan pada mobile.

## Pengembangan

- `npm start`: menjalankan Expo.
- `npm run web`: menjalankan pratinjau Web.
- `npx tsc --noEmit`: memeriksa TypeScript.
- `npx expo export --platform web`: menghasilkan build Web ke `dist/`.
- `git diff --check`: memeriksa whitespace perubahan.

Script `npm run lint` tersedia, tetapi ESLint dan konfigurasinya belum disiapkan. Jangan menjalankannya sebagai pemeriksaan baca-saja: Expo dapat memasang dependency secara otomatis.

Konfigurasi Web memakai `output: "server"`; deployment membutuhkan runtime server yang sesuai.

## Konteks proyek

Baca `AGENTS.md`, `../AGENTS.md`, dan seluruh `instrucktor/` sebelum mengubah kode. Kontrak backend berada di `../docs/API.md`. Dependency dan versi stack dipertahankan selama pembersihan template.
