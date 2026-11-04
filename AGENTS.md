# EcoTrack - Instruksi Proyek

## Ringkasan Proyek
Sistem Manajemen Sampah Cerdas untuk asesmen teknis Full Stack Developer.

## Tumpukan Teknologi
- Backend: Laravel
- Frontend: Blade
- Styling: Tailwind CSS v4
- Build tool: Vite
- Database: MySQL
- Environment: Windows 11 + XAMPP
- Version control: GitHub

## Aturan Komunikasi

1. Semua respons, penjelasan, analisis, laporan progres, dan rekomendasi harus menggunakan bahasa Indonesia.
2. Semua dokumentasi proyek dan catatan handoff antarsesi juga harus menggunakan bahasa Indonesia.
3. Nama variable, class, function, method, database table, API endpoint, dan istilah teknis dalam source code tetap mengikuti konvensi penamaan bahasa Inggris.
4. Jelaskan konsep teknis dengan bahasa Indonesia yang mudah dipahami developer.

## Aturan Pengembangan

1. Periksa kode yang sudah ada sebelum mengedit.
2. Pertahankan arsitektur Laravel yang sudah digunakan.
3. Gunakan kembali model, migration, dan controller yang sudah ada.
4. Ikuti konvensi Laravel dan praktik clean code.
5. Gunakan komponen Blade yang dapat digunakan kembali.
6. Simpan frontend assets secara lokal.
7. Pastikan UI responsif.
8. Hindari dependency yang tidak diperlukan.
9. Validasi input dan otorisasi aksi sensitif.
10. Tulis test untuk business logic yang penting.

## Aturan Database

- Jangan pernah menghapus atau me-reset database yang ada.
- Jangan pernah menghapus migration yang ada.
- Jangan pernah mengubah data production.
- Periksa relationship sebelum mengubah schema.
- Gunakan migration untuk perubahan schema.
- Pertahankan waste categories yang sudah di-seed.

## Kebutuhan Bisnis

Waste categories:
- Organic Waste
- Plastic Waste
- Paper Waste

Report statuses:
- Pending
- In Progress
- Completed
- Rejected

User roles:
- Admin
- User

## AI / Machine Learning

- Jangan mengarang hasil prediksi.
- Pisahkan integrasi ML dari Laravel.
- Laravel berkomunikasi dengan ML melalui REST API.
- Gunakan respons API yang sebenarnya saat integrasi sudah siap.

## Aturan Git

- Jangan pernah force push.
- Jangan pernah menghapus branch.
- Jangan commit atau push tanpa persetujuan user.
- Pertahankan perubahan user yang sudah ada.

## Alur Kerja

Sebelum implementasi:
1. Periksa file yang relevan.
2. Pahami arsitektur yang sudah ada.
3. Identifikasi dependency.
4. Implementasikan perubahan terkecil yang sesuai.
5. Jalankan test yang relevan.
6. Jalankan `npm run build` jika ada perubahan frontend.
7. Laporkan file yang diubah dan hasil test.

Jelaskan keputusan arsitektur yang penting agar developer dapat memahami dan mempertanggungjawabkan implementasi saat wawancara teknis.

## Penggunaan Local Agent Skills

- Gunakan skill yang relevan dengan tugas yang sedang dikerjakan.
- Baca instruksi `SKILL.md` sebelum implementasi atau mengikuti workflow skill.
- Prioritaskan pemanggilan skill secara eksplisit jika skill sangat relevan dengan tugas.
- Jangan memanggil seluruh skill sekaligus; pilih yang benar-benar diperlukan.
- Jangan menjalankan script dari skill sebelum memahami fungsinya dan dampaknya.
- Laporkan skill yang benar-benar digunakan.
- Jangan mengklaim menggunakan skill jika instruksi `SKILL.md`-nya belum dibaca.

## Aturan File Test dan Keamanan Testing

- Source test hanya boleh dibuat di `tests/Feature` atau `tests/Unit`.
- Buat test hanya untuk business logic dan fitur yang benar-benar penting.
- Jangan membuat laporan HTML coverage, screenshot, video, atau artefak tambahan kecuali diminta.
- Jangan menyimpan test data permanen di database development.
- Jangan mengubah file konfigurasi testing sebelum menjelaskan alasannya.
- Jangan menggunakan `migrate:fresh` terhadap database development.
- Jangan menghapus test yang sudah ada.

## Skill wajib setiap sesi dan perubahan

- Wajib gunakan `lean-code`, `project-context`, 'design-taste-frontend','emil-design-eng', 'impeccable.' dan `planning` pada setiap sesi dan sebelum perubahan; baca SKILL.md masing-masing jika belum dibaca dalam konteks sesi.
- Pilih rencana singkat untuk tugas kecil, gunakan kembali konteks yang sudah diverifikasi, dan buat perubahan terkecil yang memenuhi kebutuhan.
