# TASK 005 — Verifikasi integrasi pelaporan sampah

Tanggal: 4 November 2026.

## 1. Kondisi awal dan rencana

Working tree sejak awal sudah berisi perubahan audit sebelumnya pada backend, dokumentasi, dependency mobile, dan fitur mobile untracked. Seluruh perubahan itu dipertahankan. Git status dan diff diperiksa sebelum/sesudah pekerjaan. TASK ini tidak mengubah kode backend, Blade, CSS, JavaScript website, layout, schema development, atau dependency yang tercatat.

Batas mobile 1.000 karakter hasil koreksi sebelumnya dipertahankan. Kamera/galeri memakai ImagePicker SDK58, GPS memakai foreground permission dan Location SDK58, multipart melalui client terpusat, submit dikunci dengan ref, dashboard sudah refresh saat fokus. Riwayat sebelumnya hanya mengambil data pada mount/perubahan filter.

Rencana: verifikasi source kontrak → reproduksi masalah client → perubahan minimal → test unit dan HTTP dengan database terisolasi → quality gates → handoff. Tidak melanjutkan TASK006.

## 2. Kontrak Laravel yang ditemukan

Sumber: routes/api.php; Api/V1/WasteReportController dan DashboardController; Api/V1/StoreWasteReportRequest yang mewarisi StoreWasteReportRequest; WasteReportResource; WasteReport/User/WasteCategory/ReportStatusHistory; migration waste_reports dan history; docs/API.md.

| Endpoint | Auth | Request | Respons/operasi |
|---|---|---|---|
| GET /api/v1/categories | Sanctum, role:user | tanpa body | 200, kategori dari DB |
| POST /api/v1/reports | Sanctum, role:user | multipart: description, image, category_id?, latitude?, longitude? | 201 envelope success/message/data; ID, kategori, deskripsi, URL foto, koordinat, status, timestamp |
| GET /api/v1/dashboard | Sanctum, role:user | tanpa body | statistik dan lima terbaru milik user |
| GET /api/v1/reports | Sanctum, role:user | page1–1.000.000, per_page1–50, status enum | data array dan pagination; hanya milik user |
| GET /api/v1/reports/{id} | Sanctum, role:user | ID numerik | detail+status_history; 404 untuk milik orang lain |

Server: description required/string/max10000; image required/image/mimes jpeg,png,webp/max5120KB; category nullable/integer/exists; latitude nullable/numeric/-90..90; longitude nullable/numeric/-180..180. Mobile sengaja lebih ketat pada deskripsi (maksimal1.000), tetap kompatibel sebagai subset input API. Website pengguna memiliki request tersendiri: max1000 dan GPS wajib. Tidak mengubah salah satu aturan Laravel itu.

Pemilik ditentukan dari relasi pengguna terautentikasi, status awal pending; input user_id/status/AI client tidak digunakan pada create API. File disimpan bernama generated pada public/waste-reports. Penyimpanan DB dalam transaksi; file dibersihkan saat transaksi gagal. Status history awal kosong karena belum terjadi perubahan status. Foto URL public mengikuti konfigurasi disk. FK kategori nullable, FK owner ke users, koordinat decimal10,7, status defaultpending.

Client menyertakan Accept JSON dan Bearer; untuk FormData tidak memaksakan Content-Type sehingga fetch membuat boundary. Native mengirim descriptor URI/name/type, Web mengirim File. Timeout30detik; network/http/timeout/cancel/invalid-response dibedakan. Tidak ada retry POST otomatis.

## 3. Masalah dan tingkat risiko

| ID | Risiko | Bukti | Tindakan |
|---|---|---|---|
| T005-01 | P2 | validatePhoto sebelumnya membandingkan ukuran dengan <=0/>5MB, NaN lolos kedua pembandingan | Number.isFinite ditambahkan; test awal gagal lalu lulus |
| T005-02 | P2 | submitReport sebelumnya langsung mengembalikan data; payload sukses tanpa ID diterima sebagai berhasil | wajib ID safe integer positif; invalid-response memicu state uncertain existing |
| T005-03 | P2 | history.tsx menggunakan useEffect; kembali fokus tanpa remount tidak menjamin request baru | useFocusEffect/useCallback; request dibatalkan saat blur; hook test membuktikan refetch |
| T005-04 | P2 tersisa | API belum memiliki idempotency key atomik lintas client/restart | lock submit dan uncertain state diuji; tidak mengklaim exactly-once |
| T005-05 | batas verifikasi | tidak ada akses perangkat fisik untuk kamera/GPS/URI native | wajib uji perangkat; bukan hasil mock yang dianggap nyata |
| T005-06 | perbedaan terdokumentasi | mobilemax1000, APImax10000, GPS API opsional versus website wajib | aturan yang ada dipertahankan; tidak memperluas/melonggarkan validasi |

Kategori yang gagal dimuat menampilkan error/retry; sesi kedaluwarsa saat submit memicu auth.restore. Preview memakai URI dan URL blob Web dibersihkan saat foto berganti/unmount. Izin kamera native diminta saat aksi, galeri memakai system picker, GPS foreground diminta saat aksi; GPS timeout20detik. Keakuratan GPS fisik, EXIF/orientasi, dan format foto kamera aktual belum diverifikasi perangkat.

## 4. Perbaikan dan file

Produksi mobile hanya:
- mobile/src/features/reporting/service.ts: ukuran finite dan validasi konfirmasi ID. Batas1.000, format/5MB, rentang koordinat, kategori serta multipart tetap.
- mobile/src/features/reporting/history.tsx: refresh saat fokus dan cleanup saat blur, tanpa perubahan JSX/layout.

Test:
- mobile/tests/Unit/reporting.test.cjs: ukuran non-finite, ID invalid, format allowlist dan batas koordinat; test1000/1001 tetap.
- mobile/tests/Unit/report-history.test.cjs: lifecycle fokus/blur.
- mobile/tests/Unit/report-submit.test.cjs: dua klik hanya satu submit; uncertain state untuk network/timeout/invalid-response. React/Expo pada test ini adalah stub; bukan E2E perangkat.
- mobile/tests/Unit/reporting-http.cjs: source TypeScript service/client/dashboard ditranspile di memori lalu dipakai untuk HTTP nyata. Tidak memock fetch atau respons Laravel; adapter token diganti memori dan Platform=web untuk Node.
- tests/Feature/ReportingHttpIntegrationTest.php dan ReportingHttpRouter.php: server PHP lokal dinamis dengan SQLite/storage sementara, hanya untuk testing.

Dokumentasi: docs/TASK_005_REPORT.md dan mobile/instrucktor/handoff.md (ignored).

## 5. Bukti integrasi dan hasil pemeriksaan

Feature test HTTP membuat direktori unik ecotrack-report-* di TEMP, membuat database SQLite baru, menjalankan migrate (bukan migrate:fresh) dan WasteCategorySeeder pada database tersebut saja. Server memakai APP_ENV=testing, override koneksi SQLite eksplisit, cache/session array, lognull, dan public storage sementara. Test tidak membaca atau mencetak token/secret ke output. Process dihentikan dan direktori TEMP dibersihkan di finally; keberhasilan cleanup juga di-assert.

Node mengimpor source client dan service mobile aktual; register dua akun hanya di DB test, mengambil tiga kategori seed, memilih kategori API, mengirim PNG nyata dengan deskripsi1.000 dan koordinat0/180. Server memproses boundary multipart HTTP. Test memeriksa ID/status/kategori/koordinat, mengambil URL foto dan membandingkan byte, membaca dashboard (total/pending1 dan ID terbaru), daftar berfilter/pagination dan detail/history kosong. Koordinat91/181, kategori tidak ada, serta teks PHP bernama jpg ditolak422. User kedua melihat dashboard/list kosong dan detail404. Token invalid ditolak401; logout mencabut token. PDO memeriksa satu record, owner sesuai akun pertama, statuspending, history0 dan file exists. Semua fixture hilang setelah test, tidak ada laporan development permanen.

Router foto khusus test melayani storage TEMP; hasil ini membuktikan byte file/URL kontrak pada server test, bukan konfigurasi Apache/LAN produksi.

| Pemeriksaan | Hasil | Exit code |
|---|---|---:|
| node --test tests/Unit/reporting.test.cjs (reproduksi sebelum fix) | 7lulus/2gagal: NaN dan respons tanpaID | 1 |
| node --test tests/Unit/*.test.cjs (akhir) | 35lulus/0gagal | 0 |
| php vendor/phpunit/phpunit/phpunit --filter ReportingHttpIntegrationTest (awal) | 1lulus/10assertion | 0 |
| php artisan test (akhir, termasuk HTTP dengan seeded kategori dan cleanup) | 84lulus/580assertion | 0 |
| npx tsc --noEmit | lulus | 0 |
| npx expo install --check | dependency kompatibel | 0 |
| npx expo export --platform web --max-workers 1 | Exported dist, proses selesai | 0 |
| npx expo-doctor (akhir) | 20/20 | 0 |
| npm run build (website) | lulus, warning font runtime existing | 0 |
| Impeccable detect history.tsx | [] | 0 |
| git diff --check | lulus, warning konversi CRLF saja | 0 |
| npm run lint | ESLint/config belum tersedia, mencoba bootstrap dependency, dihentikan | 1 (interupsi) |

Percobaan lint dihentikan ketika Expo otomatis mencoba menambah eslint/eslint-config-expo. Dua entry otomatis package.json dibatalkan; lockfile tidak berubah. Hash final sama dengan sebelum TASK005: package.json1a4e285e6078c837fa310fab1f1ad7827048186e; package-lock.json4798c56a3d7904a3e668fa574424c0e0044e8d6c. Tidak ada dependency baru yang dipertahankan atau konfigurasi lint baru dibuat. Doctor pertama saat percobaan lint berlangsung gagal1 (19/20); setelah metadata dipulihkan Doctor20/20 exit0 dan dependency check0.

Export sempat melaporkan cache Metro tidak dapat dideserialisasi, lalu melakukan full crawl otomatis dan selesai exit0; tidak menghapus cache/source untuk menyembunyikan warning. Percobaan edit history melalui shell pertama memakai path mobile/mobile dan gagal tanpa mengubah file; diperbaiki dengan path repository yang benar. Tidak ada kegagalan runtime yang ditutupi oleh hasil command terakhir.

## 6. Yang belum diuji

- Kamera/galeri fisik Android/iOS, permission ditolak permanen, cancel picker di perangkat, dan URI native sampai server.
- GPS lokasi aktual/akurasi, permission OS/browser, secure context Web dan koneksi LAN.
- E2E layar lengkap dengan browser/perangkat; pengujian handler/hook menggunakan stub React/Expo dinyatakan terpisah.
- Server Apache/MySQL development/production, reverse proxy, serta akses foto dari jaringan luar. Database nyata yang diuji adalah SQLite terisolasi.
- ESLint karena tooling belum tersedia; tidak mengklaim lintlulus.
- Crash/network loss setelah server commit dan sebelum respons, duplicate lintas restart/client, race concurrent MySQL, kegagalan cleanup storage pada disk penuh/permission.

## 7. Perlindungan website

Tidak ada perubahan TASK005 pada app/, routes/, config/, database/, bootstrap/, resources/views, resources/css, resources/js atau layout website. Diff backend audit sebelumnya tetap dipertahankan, tidak diklaim sebagai hasil TASK005. Endpoint, Requests, Resources, Models, skema dan logika admin tidak diubah. Seluruh suite Laravel termasuk login/role/dashboard/kategori/laporan/ownership lulus. Build website lulus dengan hash aset CSS/JS existing; build bukan bukti seluruh browser flow website.

| Before | After | Why |
|---|---|---|
| Riwayat memuat saat mount | Riwayat memuat saat fokus dan membatalkan saat blur | Memperbarui data setelah kembali ke layar, tanpa redesign |
| Konfirmasi tanpaID dianggap sukses | Ditahan sebagai hasil tidak pasti | Menghindari konfirmasi palsu dan retry yang menggandakan laporan |

## 8. Risiko tersisa

Idempotensi server belum tersedia. Ref lock hanya melindungi instance layar; stateuncertain menahan retry pada instance yang sama. Reload/restart atau client lain dapat membuat duplikat; belum ada jaminan exactly-once. Foto masih public menurut kontrak existing. Perbedaan max server/mobile tetap terdokumentasi, tidak diubah untuk menyesuaikan implementasi baru. Masa kedaluwarsa token dan advisory tooling dari audit lama berada di luar perubahan TASK005.

## 9. Hasil dan rekomendasi

Definition of Done integrasi pada batas service HTTP–Laravel–DB/storage testing terbukti: multipart diproses, koordinat tervalidasi, kepemilikan/status benar, foto tersimpan, laporan ditemukan kembali di dashboard/riwayat/detail. Pengujian ini tidak membuktikan pengalaman perangkat fisik. Lint dan verifikasi native tetap terbuka.

Prioritas sesudah peninjauan TASK005: uji kamera/galeri/GPS dan submit Android/iOS nyata dengan backend staging terisolasi; verifikasi LAN/HTTPS/foto; putuskan kebijakan idempotency sebelum perubahan schema/kontrak. Tidak otomatis memulai TASK006.

Skill yang diterapkan: lean-code (reuse client dan komponen), planning (scope kecil dan quality gates), project-context (verifikasi source/handoff dan preservation), design-taste-frontend (layar operasional, tanpa redraw), emil-design-eng (feedback dan lifecycle), impeccable harden (state gagal/fokus dan detector), testing-strategy (unit untuk handler, HTTP terisolasi untuk persistence), systematic-debugging (dua reproduksi gagal sebelum fix). Keenam skill wajib dan tambahan dibaca; tidak memasang skill baru. Referensi API resmi diperiksa sesuai expo58.0.23: [ImagePicker SDK58](https://docs.expo.dev/versions/v58.0.0/sdk/imagepicker/), [Location SDK58](https://docs.expo.dev/versions/v58.0.0/sdk/location/), [indeks Expo](https://docs.expo.dev/llms.txt).
