# Audit Visual Website EcoTrack

Tanggal: 2 November 2026  
Metode Impeccable: dual-agent (/root/visual_review_a dan /root/visual_review_b), keduanya read-only.  
Status: audit dan rekomendasi; tidak ada redesign atau perubahan source.

## Ringkasan

Login memiliki hierarki yang jelas dan tetap terbaca pada empat ukuran viewport. Area operasional Admin memiliki fondasi komponen yang dapat dipakai ulang, tetapi tipografi, navigasi, status, dan tabel belum konsisten. Area User terasa seperti kumpulan halaman terbatas, bukan workspace: lebarnya terlalu sempit untuk konten laporan dan tidak menyediakan navigasi yang sebanding dengan Admin.

Validasi visual langsung hanya dapat dilakukan untuk login. Halaman Admin dan User yang memerlukan autentikasi ditinjau dari Blade, CSS, JavaScript, route, dan handoff; tidak ada sesi atau kredensial lokal yang tersedia. Hasilnya bukan klaim inspeksi browser untuk halaman terlindungi. Empat screenshot login disertakan di bawah.

## Metode dan cakupan bukti

- Membaca AGENTS.md, docs/CODEX_HANDOFF.md, halaman Blade dan komponen terkait, stylesheet, JavaScript sidebar, serta route yang relevan.
- Membuka login lokal dengan Playwright pada 1440×900, 1280×800, 768×1024, dan 390×844. Tidak mengirim form login dan tidak membuat kredensial.
- Login menghasilkan accessibility tree dengan landmark main dan region, heading, label field Email/Password, checkbox, dan tombol. Tidak terlihat error atau warning console pada origin 127.0.0.1:8000.
- Dua reviewer melakukan penilaian terpisah. Detektor Impeccable untuk direktori view mengembalikan [] (0 temuan otomatis); temuan manual tetap penting karena keterbatasan detektor pada perilaku interaksi dan aksesibilitas.
- Halaman error, loading, dan state kosong tidak dipicu melalui browser. Penilaian state tersebut adalah pembacaan source, bukan observasi runtime. Tidak menjalankan axe atau audit screen reader.
- Tidak memakai data production, tidak mengubah database, dan tidak menjalankan test/build karena task ini audit visual tanpa perubahan frontend.

## Skor heuristik Impeccable

Skor reviewer A: **23/40 — Acceptable**. Ini penilaian heuristik, bukan skor standar atau hasil pengukuran otomatis.

| Heuristik | Nilai / 4 | Catatan |
|---|---:|---|
| Visibilitas status sistem | 2 | Status laporan ada, tetapi konsistensi penyajiannya berbeda antarhalaman. |
| Kesesuaian dengan bahasa pengguna | 3 | Domain laporan dan kategori mudah dikenali. |
| Kontrol dan kebebasan pengguna | 2 | Beberapa kontrol navigasi tidak memiliki tujuan aktif; drawer perlu perbaikan keyboard. |
| Konsistensi dan standar | 2 | Status, ukuran tabel, dan pola shell User/Admin tidak seragam. |
| Pencegahan kesalahan | 3 | Form memiliki validasi; konfirmasi hapus tersedia pada alur tertentu. |
| Pengenalan dibanding mengingat | 3 | Badge dan label membantu, tetapi beberapa aksi dan navigasi kurang terlihat. |
| Fleksibilitas dan efisiensi | 2 | User tidak memiliki navigasi dan pintasan kerja yang cukup. |
| Desain minimal dan fokus | 3 | Login fokus; dashboard memakai pola dekoratif/metric generik. |
| Pemulihan dari error | 2 | Ada pesan validasi inline, tetapi asosiasi aksesibilitas dan ringkasan error belum konsisten. |
| Bantuan dan dokumentasi | 1 | Bantuan kontekstual di UI masih minim. |

## Kondisi per halaman

| Halaman | Bukti | Kondisi dan temuan |
|---|---|---|
| Admin — Login | Browser, empat viewport | Form terpusat dan sederhana, hierarki heading/field/CTA jelas. Pada 390 px tidak ada clipping; teks pendukung membungkus dua baris. Tidak terlihat link registrasi. Tampilan rapi, tetapi selain emerald/logo, identitas produk masih ringan. |
| Admin — Dashboard | Source saja | Empat KPI berukuran sama, grafik/donut dan tabel terbaru. Urutan metrik belum menonjolkan tugas yang perlu segera ditangani. Label 10 px dan metadata 10–11 px terasa kecil. Kontrol periode/search perlu sesuai data dan aksi nyata. |
| Admin — Daftar laporan | Source saja | Filter responsif dan tabel dapat di-scroll horizontal (min-width 850 px). Header kecil dan tabel tidak memiliki caption/scope kolom. Empty state belum menawarkan CTA/reset filter yang jelas. |
| Admin — Detail laporan | Source saja | Layout konten utama dengan rail samping pada layar lebar dan bertumpuk pada breakpoint lebih kecil; foto dan riwayat memberi konteks. Form status memiliki pesan inline, tetapi relasi error-field belum lengkap. Aksi destruktif memakai konfirmasi native. |
| Admin — Buat laporan | Source saja | Form mengikuti pola form laporan bersama. Validasi ada, tetapi required state tidak selalu tampak/semantik secara konsisten; error belum selalu dihubungkan dengan field. Runtime belum terverifikasi. |
| Admin — Edit laporan | Source saja | Menggunakan pola yang sama dengan create. Tidak ada preview foto pengganti sebelum submit, sehingga pilihan file sulit diverifikasi. |
| Admin — User Management | Source saja | Daftar menyediakan pencarian, filter role, pagination, dan hitungan laporan. Tabel minimal 700 px sehingga viewport kecil dapat memerlukan scroll horizontal; caption dan scope kolom sudah lebih baik daripada tabel laporan. |
| Admin — Detail User | Source saja | Ringkasan informasi dan hitungan laporan tersedia, tetapi tidak ada daftar laporan terkait. Bagian hapus terpisah dan perlu mempertahankan penekanan destruktif yang jelas. Password tidak ditampilkan. |
| Admin — Buat User | Source saja | Form bersama max-width 2xl, label dan error tersedia; ada bantuan password. Tidak terlihat langsung, jadi ritme visual dan validasi runtime belum dinilai. |
| Admin — Edit User | Source saja | Menggunakan form bersama dan mendukung pengelolaan role. Pastikan role, perubahan email, dan aksi berisiko tetap memiliki konteks visual yang jelas. Runtime belum diperiksa. |
| User — Dashboard | Source saja | Konten dibatasi max-width-md, terasa seperti kartu login pada desktop. Navigasi terbatas pada keluar dan tautan detail; tidak ada CTA buat laporan pada website. Status berupa teks kapitalisasi berbeda dari badge Admin. |
| User — Detail laporan | Source saja | Menggunakan shell sempit yang sama; status dan riwayat memberi konteks, foto tersedia. Tidak ada navigasi kembali/workspace User yang konsisten selain tautan halaman yang tersedia. |

Login adalah tampilan yang sama untuk Admin dan User. Screenshot memperlihatkan halaman tersebut di empat viewport:

### Desktop 1440×900

![Login EcoTrack 1440×900](../output/playwright/login-1440x900.png)

### Laptop 1280×800

![Login EcoTrack 1280×800](../output/playwright/login-1280x800.png)

### Tablet 768×1024

![Login EcoTrack 768×1024](../output/playwright/login-768x1024.png)

### Mobile 390×844

![Login EcoTrack 390×844](../output/playwright/login-390x844.png)

## Temuan utama menurut prioritas

### P0 — fungsi dan akses yang mengganggu alur

1. Sidebar memiliki tautan Kategori dan Pengaturan ke /admin/categories dan /admin/settings yang belum mempunyai halaman/route aktif. Tombol notifikasi pada navbar juga tidak memiliki aksi. Kontrol yang terlihat tetapi tidak bekerja membingungkan.
2. Drawer sidebar mobile dapat ditutup secara visual, tetapi tautannya masih berada di urutan Tab; implementasi belum memindahkan, membatasi, dan mengembalikan fokus. Tambahkan pengelolaan fokus dan state tersembunyi/inert yang sepadan dengan drawer modal.
3. Area User terlalu sempit untuk fungsi laporan dan tidak menyediakan shell navigasi/CTA kerja yang memadai. Tentukan dahulu apakah website memang perlu membuat laporan; jika ya, alur tersebut perlu menjadi bagian workspace User.

### P1 — konsistensi data dan responsive

1. Tabel laporan Admin memakai lebar minimum 850 px tanpa caption/scope, sedangkan tabel User sudah memiliki semantik tersebut. Pada tablet/mobile tabel perlu pola scroll berlabel atau row-card yang tetap menjaga akses ke status dan aksi.
2. Status laporan berbeda format antara User (teks lowercase yang dikapitalisasi) dan Admin (badge semantik). Samakan label, warna, dan ikon; jangan mengandalkan warna saja.
3. Dashboard mengulang pola empat KPI setara dan panel putih generik. Prioritaskan antrean yang perlu ditangani, status operasional, dan laporan terbaru berdasarkan kepentingan kerja.
4. Metadata 10–11 px terlalu kecil untuk informasi rutin. Naikkan ukuran teks sekunder dan jaga body terbaca pada zoom/mobile.
5. Form laporan tidak menyediakan preview foto baru; sebagian error inline tidak ditautkan ke field melalui aria-describedby/aria-invalid.

### P2 — sentuhan visual dan rasa produk

1. Gaya kartu putih, border tipis, sudut bulat, shadow kecil, KPI setara, dan ikon chip yang diulang membuat dashboard terasa seperti template SaaS umum. Gunakan hierarki yang ditentukan oleh pekerjaan pengelolaan sampah, bukan dekorasi tambahan.
2. Palet mencampur token --color-primary #006c49, kelas Emerald, permukaan bernuansa biru (#f8f9ff/#eff4ff), dan utilitas Slate. Satukan token tanpa menghilangkan logo/identitas emerald.
3. Empty state tersedia di beberapa area, tetapi tidak semuanya memberi langkah berikutnya. Loading state tidak tampak pada source server-rendered; tambahkan hanya bila ada operasi async yang memerlukannya.

## Responsive

- **1440 dan 1280:** login terpusat, proporsional, dan bebas clipping. Halaman lain belum diuji langsung karena memerlukan sesi.
- **768:** login tetap terbaca. Sidebar Admin beralih ke drawer pada breakpoint di bawah 1024 px; visualnya didukung overlay, tetapi fokus keyboard belum dikelola tuntas.
- **390:** login tidak clipping dan teks pendukung membungkus baik. Tabel laporan (850 px) dan user (700 px) mengandalkan scroll horizontal; pengalaman sentuh/scroll belum diuji browser.
- Ukuran untuk halaman terlindungi merupakan penilaian implementasi/source dan perlu diverifikasi saat redesign dengan sesi lokal yang sah.

## Aksesibilitas dan UX

- Login memiliki landmark, heading, label input, checkbox, dan tombol yang terbaca di accessibility tree.
- Belum ada skip link ke konten utama.
- Tabel dashboard/laporan Admin perlu caption dan scope kolom. Tabel User Management sudah memiliki keduanya.
- Error form laporan belum konsisten terhubung ke field dan tidak ada ringkasan error global yang memudahkan navigasi.
- Sidebar mobile perlu mengeluarkan tautan dari urutan Tab saat tertutup, memindahkan fokus saat dibuka, mengelola fokus bila modal, mengembalikan fokus saat ditutup, serta tetap mendukung Escape.
- Stylesheet mengaktifkan smooth scroll/transisi tanpa aturan prefers-reduced-motion.
- Perkiraan kontras text-slate-400 (#94a3b8) terhadap surface #f8f9ff adalah sekitar 2,44:1 dan pada putih sekitar 2,56:1, di bawah 4,5:1 untuk teks normal. text-slate-500 kira-kira 4,53:1 terhadap #f8f9ff, sangat dekat ambang; verifikasi warna final dan ukuran/ketebalan aktual.
- Jangan mengandalkan warna status saja; sertakan teks. Periksa target pointer, zoom/reflow, focus visible, dan urutan keyboard saat implementasi.

## State kosong, loading, dan error

- **Kosong:** source memuat empty state pada daftar laporan, dashboard User, riwayat detail, dan daftar User/dashboard tertentu. Beberapa state belum memiliki CTA kontekstual atau reset filter.
- **Loading:** tidak ditemukan skeleton/loading state relevan pada halaman server-rendered. Tidak perlu menambah animasi jika tidak ada waktu tunggu async; bila ada, beri indikator yang diumumkan assistive technology.
- **Error:** pesan validasi inline tersedia pada sejumlah form dan login memiliki alur validasi; asosiasi label-error perlu dirapikan. Tampilan error server dan respons submit belum diuji melalui browser.

## Komponen yang dipertahankan dan yang perlu dirancang ulang

| Pertahankan | Rancang ulang atau konsolidasikan |
|---|---|
| Logo/wordmark EcoTrack dan emerald sebagai aksen brand. | Shell User dan navigasi kerja yang konsisten di mobile/desktop. |
| Font Inter lokal dan komponen field auth yang digunakan bersama. | Sidebar drawer: fokus, state tertutup, semantik, dan indikator menu aktif. |
| Komponen badge status/kategori, stat card, serta dashboard card sebagai fondasi. | Dashboard Admin: hierarki operasional, skala tipografi, relevansi filter/kontrol. |
| Pemisahan Blade component dan pola validasi form yang ada. | Tabel laporan responsive dan semantiknya; status konsisten lintas role. |
| Empty state yang sudah tersedia sebagai awal pola. | Form laporan: asosiasi error, required state, dan preview foto pengganti. |
| Pola form User Management dengan bantuan password. | Token warna/spacing/type dan varian tombol/input/focus/destructive. |

## Rekomendasi design system

1. **Brand dan warna:** pertahankan emerald untuk aksi utama/brand; tetapkan token tunggal primary, status, surface, border, teks, dan focus ring. Uji kontras tiap pasangan teks/latar.
2. **Tipografi:** gunakan Inter; tetapkan skala page title, section title, body, label, metadata. Hindari metadata penting di bawah 12 px; target body umum 14–16 px.
3. **Spacing dan layout:** tetapkan skala jarak, lebar konten per jenis halaman (form, daftar, detail), dan aturan breakpoint. Hindari shell max-width-md untuk daftar/dashboard.
4. **Komponen:** standar tombol primary/secondary/tertiary/destructive, input, pesan validasi, status/category badge, KPI/metric, empty state, tabel, dan navigasi/drawer.
5. **Permukaan:** gunakan elevasi seperlunya dan konsisten. Kurangi kartu dekoratif berulang; bedakan panel berdasarkan fungsi/data.
6. **Interaksi:** sediakan hover/focus/disabled/loading yang jelas, target klik memadai, reduced motion, dan umpan balik untuk setiap kontrol.

## Urutan redesign yang direkomendasikan

1. **Fondasi aksesibilitas dan shell:** skip link, focus states, drawer keyboard, navigasi User, tautan mati/aksi tanpa fungsi.
2. **Design tokens dan komponen dasar:** warna, skala type/spacing, tombol, field/error, badge, empty state.
3. **Dashboard Admin dan data table:** susun ulang prioritas operasional; semantik tabel dan tampilan mobile; samakan status.
4. **Alur laporan:** form create/edit/detail, preview foto, feedback validasi dan state kosong.
5. **User Management dan dashboard/detail User:** lebar/layout, filter/tabel, tindakan aman, konsistensi shell.
6. **Verifikasi visual dan accessibility:** seluruh 12 halaman pada 1440, 1280, 768, dan 390 px; cek keyboard, zoom/reflow, kontras, dan state runtime.

## Skill yang digunakan

Skill lokal yang dicek tersedia: design-taste-frontend, web-design-guidelines, impeccable, baseline-ui, emil-design-eng, frontend-design, frontend-craft, check-fix-accessibility, dan playwright.

Yang benar-benar dipakai:

- project-context untuk membaca konteks repo/handoff dan mencatat hasil sesi.
- design-taste-frontend untuk arah visual dan menghindari pola dashboard generik.
- impeccable untuk kritik heuristik dan penilaian reviewer terpisah. Perintah context tidak menghasilkan output yang terlihat dan repo tidak memiliki PRODUCT.md/DESIGN.md; penilaian didasarkan pada source dan handoff yang tersedia.
- baseline-ui untuk hierarki tipografi, spacing, dan konsistensi.
- emil-design-eng untuk evaluasi interaksi dan format temuan before/after/why.
- web-design-guidelines dan check-fix-accessibility untuk UX, semantik, keyboard, reduced motion, dan kontras.
- playwright untuk inspeksi browser login dan screenshot empat ukuran.

Referensi guideline yang diperiksa: [Web Interface Guidelines](https://raw.githubusercontent.com/vercel-labs/web-interface-guidelines/main/command.md). Detektor otomatis Impeccable tidak menemukan temuan, tetapi audit manual dan pemeriksaan keyboard tetap diperlukan.

## Batasan dan status implementasi

Tidak ada source, route, migration, database, atau konfigurasi yang diubah. Tidak ada test maupun build yang dijalankan. Login yang terlihat di browser memakai server lokal pada root 127.0.0.1:8000; ketika aplikasi dibuka lewat subpath XAMPP /smart-waste-management/public/login, referensi font /assets/fonts/inter/... merespons 404. Periksa konfigurasi asset/base path bila mode subpath tersebut merupakan target deployment.

Screenshot yang disimpan adalah empat screenshot login di output/playwright/. Tidak tersedia screenshot halaman Admin/User terlindungi karena sesi lokal yang sah tidak tersedia dan kredensial tidak ditebak atau dibuat.




## TASK 009 - Inspeksi Visual dengan Sesi Terautentikasi (2026-11-02)

### Metode dan batasan sesi

- Menggunakan Playwright CLI pada browser Edge lokal. Login dilakukan manual oleh pengguna; password tidak diminta, dibaca, atau disimpan.
- Sesi yang tersedia adalah **Admin**. Halaman Manajemen Pengguna menampilkan satu akun, yaitu Admin yang sedang login. Tidak ada akun dengan role User yang dapat dipakai. Saya tidak membuat akun atau mengubah database.
- Screenshot dibuat sebagai full-page pada viewport 1440x900, 1280x800, 768x1024, dan 390x844. Nama/email, deskripsi, koordinat, catatan, serta foto laporan diganti/disamarkan sebelum screenshot; tidak ada password atau token dalam screenshot.
- Semua halaman terlindungi di bawah ini dibuka hanya dengan GET. Tidak mengirim form, mengganti status, menghapus data, atau menjalankan aksi yang mengubah database.

### Halaman yang diperiksa langsung di browser

| Halaman | URL dan hasil | Screenshot |
|---|---|---|
| Admin Dashboard | /admin — HTTP 200, seluruh empat viewport | [1440x900](../output/playwright/website-audit/admin-dashboard-1440x900.png) · [1280x800](../output/playwright/website-audit/admin-dashboard-1280x800.png) · [768x1024](../output/playwright/website-audit/admin-dashboard-768x1024.png) · [390x844](../output/playwright/website-audit/admin-dashboard-390x844.png) |
| Admin Daftar Laporan | /admin/reports — HTTP 200, seluruh empat viewport | [1440x900](../output/playwright/website-audit/admin-reports-1440x900.png) · [1280x800](../output/playwright/website-audit/admin-reports-1280x800.png) · [768x1024](../output/playwright/website-audit/admin-reports-768x1024.png) · [390x844](../output/playwright/website-audit/admin-reports-390x844.png) |
| Admin Detail Laporan | /admin/reports/1 — HTTP 200, seluruh empat viewport | [1440x900](../output/playwright/website-audit/admin-report-detail-1-1440x900.png) · [1280x800](../output/playwright/website-audit/admin-report-detail-1-1280x800.png) · [768x1024](../output/playwright/website-audit/admin-report-detail-1-768x1024.png) · [390x844](../output/playwright/website-audit/admin-report-detail-1-390x844.png) |
| Admin User Management | /admin/users — HTTP 200, seluruh empat viewport | [1440x900](../output/playwright/website-audit/admin-users-1440x900.png) · [1280x800](../output/playwright/website-audit/admin-users-1280x800.png) · [768x1024](../output/playwright/website-audit/admin-users-768x1024.png) · [390x844](../output/playwright/website-audit/admin-users-390x844.png) |
| Admin Detail Pengguna | /admin/users/1 — HTTP 200, seluruh empat viewport; detail akun Admin sendiri | [1440x900](../output/playwright/website-audit/admin-user-detail-1-1440x900.png) · [1280x800](../output/playwright/website-audit/admin-user-detail-1-1280x800.png) · [768x1024](../output/playwright/website-audit/admin-user-detail-1-768x1024.png) · [390x844](../output/playwright/website-audit/admin-user-detail-1-390x844.png) |

### Halaman yang tidak dapat diperiksa

| Halaman | Hasil | Alasan |
|---|---|---|
| User Dashboard | /dashboard — HTTP 403 untuk sesi Admin | Middleware menolak role Admin; daftar User Management hanya menampilkan akun Admin. |
| Detail laporan milik User | /dashboard/reports/1 — HTTP 403 untuk sesi Admin | Middleware role User menolak sesi Admin. Tidak ada akun User yang dapat digunakan tanpa membuat akun baru, yang dilarang pada task ini. |

Karena itu, tidak ada screenshot User Dashboard atau detail laporan User. Batasan ini adalah kegagalan akses akibat role/sesi yang tersedia, bukan kesimpulan bahwa halaman User rusak.

### Temuan visual langsung

1. **Admin Dashboard**
   - Desktop menampilkan sidebar tetap, navbar, empat KPI setara, grafik, distribusi kategori, dan tabel laporan terbaru. Konten berhierarki jelas, tetapi dashboard memerlukan scroll vertikal bahkan pada laptop karena semua kartu ditampilkan penuh berurutan setelah grafik.
   - Pada 768 px KPI berubah menjadi dua kolom, grafik dan kategori tersusun vertikal. Tabel terbaru lebih lebar dari area konten dan memiliki scroll horizontal internal.
   - Pada 390 px KPI tersusun satu kolom, sehingga ringkasan sangat panjang sebelum grafik dan tabel terlihat. Grafik memakai ruang vertikal besar untuk data yang sedikit; tabel terbaru hanya memperlihatkan sebagian kolom dan harus digeser mendatar.
   - Navbar mobile menampung menu, notifikasi, avatar, dan keluar tanpa clipping pada screenshot.

2. **Daftar laporan Admin**
   - Pada desktop filter dan tombol “Buat laporan” tertata dalam satu area, tabel lebar dan mudah dipindai.
   - Pada mobile filter menjadi bertumpuk dan tombol CTA tetap jelas. Tabel terpotong sesuai lebar viewport; scrollbar horizontal terlihat, namun kolom status/aksi berada di luar tampilan awal.
   - Status “Menunggu” memakai badge berwarna dan label teks. Tombol aksi berbentuk tautan, bukan kontrol besar; masih memadai untuk tabel desktop tetapi target sentuhnya kecil.

3. **Detail laporan Admin**
   - Pada desktop informasi/foto mengambil dua pertiga area dan form status/riwayat berada di rail kanan. Pada mobile semua bagian bertumpuk menjadi satu kolom; tombol Edit/Hapus tetap bersebelahan dan form status melebar penuh.
   - State riwayat kosong tampil sebagai teks “Belum ada perubahan status.” tanpa ilustrasi, konsisten dengan gaya minimal halaman.
   - Form status menggunakan field berlabel dan tombol utama yang jelas. Tombol Hapus merah memiliki kontras visual kuat. Foto pada screenshot diganti placeholder untuk menjaga privasi; sumber gambar runtime terverifikasi termuat, tetapi isi fotonya tidak disimpan/dinilai.
   - Jarak vertikal antara informasi dan form terasa panjang pada viewport mobile, namun urutan baca tetap jelas.

4. **User Management Admin**
   - Desktop memiliki filter pencarian/role dan tabel yang lapang. Di viewport mobile filter bertumpuk, tabel memakai scroll horizontal; kolom role/jumlah/aksi tidak langsung tampak seluruhnya.
   - Akun yang terlihat hanya Admin yang sedang digunakan. Identitas pada screenshot sengaja disembunyikan.
   - CTA “Tambah pengguna” tetap terlihat dan mudah dikenali pada mobile.

5. **Detail pengguna Admin**
   - Pada desktop kartu detail maksimal lebarnya sekitar tiga perempat konten, menyisakan bidang kosong kanan yang luas. Aksi edit berada di kanan atas.
   - Pada mobile kartu melebar hampir penuh dan seluruh properti tersusun satu per satu. Ini tetap terbaca, tetapi menambah panjang halaman. Password tidak ditampilkan.
   - Bagian hapus akun tidak muncul karena halaman yang diperiksa adalah detail akun yang sedang login; ini sesuai kondisi UI pada akun sendiri.

### Interaksi, keyboard, dan semantik yang diuji langsung

- Tautan “Detail” laporan berubah menjadi bergaris bawah saat hover. Saat fokus, tautan memiliki outline solid hijau sekitar 1.6 px.
- Pada viewport 390 px, urutan Tab dimulai dari link dan item sidebar yang berada di luar layar (koordinat X negatif dan visibility visible), sebelum mencapai tombol pembuka menu. Ini membuat keyboard pengguna dapat berhenti pada kontrol yang tidak terlihat.
- Tombol menu mengubah aria-expanded false/true; Escape menutup drawer. Fokus setelah Escape tetap pada tombol toggle. Saat drawer dibuka, fokus tidak otomatis dipindahkan ke link pertama.
- Secara runtime, tabel User Management memiliki caption dan header kolom berscope. Tabel Daftar Laporan tidak memiliki caption dan header tidak menyatakan scope kolom.
- Lebar dokumen pada viewport mobile tidak melampaui viewport; tabel menangani lebar ekstra di dalam area scroll horizontalnya. Ini menghindari scroll horizontal untuk seluruh halaman, tetapi sebagian data tetap tersembunyi sampai tabel digeser.

### Temuan yang hanya berasal dari source code

Temuan berikut merupakan tindak lanjut dari TASK 008 dan tidak diklaim sebagai perilaku yang dikonfirmasi browser pada sesi ini:

- Tautan Kategori dan Pengaturan sidebar mengarah ke halaman yang belum tersedia, dan tombol Notifikasi tidak memiliki alur yang diimplementasikan.
- Shell User masih memakai batas lebar sempit dan belum memiliki navigasi setara workspace Admin.
- Token warna utama, emerald, slate, dan surface kebiruan masih bercampur; metadata 10–11 px dan kontras text-slate-400 perlu ditinjau.
- Form laporan belum konsisten menghubungkan pesan validasi ke field; halaman Edit belum memberi preview foto pengganti.
- Stylesheet belum memiliki penanganan prefers-reduced-motion.

### Rekomendasi redesign dari hasil inspeksi

1. **P0 — navigasi mobile:** keluarkan link sidebar dari urutan Tab saat drawer tertutup; saat dibuka pindahkan fokus ke konten drawer; saat ditutup kembalikan fokus ke toggle. Pertahankan Escape dan aria-expanded.
2. **P1 — data pada mobile:** pertimbangkan row-card untuk tabel laporan dan pengguna, atau perjelas affordance scroll serta pastikan kolom aksi mudah ditemukan tanpa geser berulang.
3. **P1 — dashboard:** pada layar kecil padatkan KPI dan ringkas tinggi grafik ketika data sedikit. Prioritaskan laporan yang perlu tindakan dan pertimbangkan ringkasan yang lebih singkat di atas fold.
4. **P1 — detail laporan:** pertahankan pemisahan informasi/form status pada desktop, lalu rapikan jarak vertikal dan urutan aksi pada mobile. Beri konteks destruktif yang kuat dan konsisten untuk Hapus.
5. **P2 — detail pengguna:** gunakan grid dua kolom yang proporsional untuk layar lebar serta pertahankan susunan satu kolom untuk mobile; kurangi ruang kosong desktop yang tidak membawa fungsi.
6. **P2 — konsistensi dan aksesibilitas:** samakan semantik tabel, ukuran teks metadata, focus ring, badge status, dan aturan reduced motion. Verifikasi kontras setelah token ditetapkan.

Pemeriksaan ini hanya menavigasi halaman dan membuka/menutup drawer; tidak ada submit, perubahan status, penghapusan, migration, perubahan database, test, build, commit, atau push.