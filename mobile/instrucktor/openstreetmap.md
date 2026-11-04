# Preview lokasi OpenStreetMap

Sesuai keputusan pengguna, Android/Expo Go dan Web memakai komponen bersama `src/features/reporting/location-map.tsx`. Preview tile memakai HTTPS OpenStreetMap tanpa API key/billing, dengan pin merah, cache, atribusi, dan loading/error/retry. Tidak ada integrasi Google Maps aktif atau dependency react-native-maps.

Tile yang dimuat hanya mencakup area preview GPS terkini. Native mengirim User-Agent EcoTrack; Web memakai identitas browser. Pertahankan cache dan atribusi sesuai https://operations.osmfoundation.org/policies/tiles/. Layanan publik tidak menjamin ketersediaan. Ini preview statis, bukan peta pan/zoom; koordinat di luar cakupan Web Mercator tetap tersedia sebagai teks.

Tombol Ambil Ulang Lokasi, akurasi/alamat, dan perlindungan koordinat saat GPS gagal tetap dipertahankan. Perbaikan URL/fallback foto dan background login TASK006 tidak dibatalkan. Foto laporan masih memerlukan perbaikan akses storage server yang sebelumnya menghasilkan HTTP403; jangan mengklaim sudah tampil nyata.
