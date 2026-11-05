# EcoTrack Machine Learning Service

Layanan ini mengklasifikasikan foto sampah ke dalam tiga kelas: **Organic Waste**, **Paper Waste**, dan **Plastic Waste**. Model menggunakan MobileNetV2 dengan bobot ImageNet sebagai backbone transfer learning, lalu menambahkan classifier tiga kelas. FastAPI menyajikan prediksi sebagai REST API terpisah dari Laravel.

## Dataset dan atribusi

Dataset bersumber dari [RealWaste oleh Sam Single, Saeid Iranmanesh, dan Raad Raad (2023)](https://github.com/sam-single/realwaste), dengan rujukan [UCI Machine Learning Repository](https://archive.ics.uci.edu/dataset/908/realwaste) dan [DOI 10.24432/C5SS4G](https://doi.org/10.24432/C5SS4G). Script memilih dan memproses foto asli, bukan membuat gambar sintetis.

Subset EcoTrack terdiri atas **900 gambar**:

| Kelas EcoTrack | Jumlah | Label sumber RealWaste |
|---|---:|---|
| Organic Waste | 300 | Food Organics |
| Paper Waste | 300 | Paper |
| Plastic Waste | 300 | Plastic |

Dengan seed 42, tiap kelas dibagi menjadi 210 train, 45 validation, dan 45 test. Total pembagian adalah 630 train, 135 validation, dan 135 test (70% / 15% / 15%). Manifest mencatat revisi sumber, pemetaan kelas, URL sumber, dan hash gambar.

Dataset lengkap tidak disimpan atau dilacak dalam Git. Salinan dataset dan manifest tersedia pada lingkungan kerja ini; dataset dapat direproduksi dari repository publik dengan `prepare_dataset.py`.

### Lisensi sumber

Terdapat perbedaan pernyataan lisensi: README repository pembuat menyebut **CC BY-NC-SA 4.0**, sedangkan halaman UCI menyebut **CC BY 4.0**. Ketidaksesuaian ini belum diselesaikan. Sampai pemilik dataset memberi klarifikasi, perlakukan foto menurut syarat yang lebih restriktif CC BY-NC-SA 4.0: berikan atribusi, gunakan non-komersial, dan patuhi share-alike. Lihat [DATA_SOURCE.md](DATA_SOURCE.md) sebelum penggunaan atau distribusi di luar asesmen.

## Persiapan dan instalasi

Gunakan Python 3.11. Dari PowerShell, buka direktori layanan dan buat environment virtual:

```powershell
cd ml-service
py -3.11 -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install --upgrade pip
pip install -r requirements-data.txt
```

Jika aktivasi PowerShell dibatasi, panggil executable `..\.venv\Scripts\python.exe` secara langsung atau gunakan jalur Python environment tersebut untuk perintah berikutnya. Jangan mengubah execution policy sistem hanya untuk proyek ini.

## Menyiapkan dataset

```powershell
python prepare_dataset.py --per-class 300 --seed 42
```

Script mengunduh foto dari repository RealWaste melalui GitHub, mengubahnya ke RGB dan ukuran 224 × 224, memeriksa unduhan dan duplikasi sumber, lalu menulis `dataset/manifest.json`. Diperlukan koneksi internet. Script menolak folder output yang sudah berisi data agar split tidak tercampur; untuk membangun salinan lain, tentukan direktori output baru dengan `--output`.

Struktur hasil:

```text
dataset/
  train/organic/  train/paper/  train/plastic/   # 210 per kelas
  val/organic/    val/paper/    val/plastic/     # 45 per kelas
  test/organic/   test/paper/   test/plastic/    # 45 per kelas
  manifest.json
```

## Training, fine-tuning, dan evaluasi

Pasang dependency model/API dan jalankan training dari `ml-service`:

```powershell
pip install -r requirements.txt
python train.py --epochs 18 --fine-tune-epochs 8 --batch-size 16
```

Training tahap pertama membekukan backbone MobileNetV2 dan melatih classifier. Tahap fine-tuning membuka sebagian layer akhir, tetap membekukan batch normalization, lalu memilih bobot berdasarkan validation loss. Test split dipakai untuk evaluasi setelah pemilihan model selesai, bukan untuk pemilihan model. Bobot awal ImageNet diunduh oleh TensorFlow saat pertama kali dibutuhkan.

Artefak model yang tersedia:

```text
artifacts/
  ecotrack.keras
  labels.json
  metrics.json
  classification_report.txt
  confusion_matrix.png
  training_history.json
```

`metrics.json` yang tersedia mencatat **akurasi test 93,33% pada 135 gambar**. Confusion matrix menunjukkan 42 dari 45 gambar benar pada masing-masing kelas. Ini adalah hasil artefak evaluasi lokal, bukan klaim hasil training ulang pada sesi dokumentasi ini. Nilai tersebut berlaku untuk test split RealWaste yang digunakan.

## Menjalankan FastAPI

Dari `ml-service`, dengan environment virtual aktif dan artefak model tersedia:

```powershell
uvicorn api:app --host 0.0.0.0 --port 8001
```

- `GET /health` — mengembalikan status layanan dan apakah model termuat.
- `POST /predict` — menerima `multipart/form-data` dengan field file bernama `image`; format JPEG, PNG, atau WebP hingga 5 MB.
- Swagger UI tersedia di `http://127.0.0.1:8001/docs` saat layanan berjalan di komputer yang sama.

Contoh request lokal:

```powershell
curl.exe -X POST "http://127.0.0.1:8001/predict" -F "image=@C:\path\to\sample.jpg"
```

Layanan memvalidasi file gambar, menerapkan orientasi EXIF, mengubahnya ke RGB dan ukuran 224 × 224, lalu mengembalikan kelas, slug, confidence, probabilitas per kelas, dan identitas model. API memerlukan `artifacts/ecotrack.keras` dan `artifacts/labels.json` saat startup.

## Integrasi Laravel → FastAPI

Laravel tetap menjadi backend utama. Saat laporan baru dikirim, `WasteClassifier` membaca `ML_SERVICE_URL` dan mengirim foto ke alamat tersebut dengan akhiran `/predict`. Contoh konfigurasi lokal pada `.env`:

```dotenv
ML_SERVICE_URL=http://127.0.0.1:8001
```

FastAPI sebaiknya dijalankan pada host/port yang dapat diakses oleh proses Laravel. Laravel memvalidasi slug, label, dan confidence sebelum menyimpan kategori AI serta confidence dalam persen. Prediksi tidak mengganti kategori pilihan pengguna. Jika URL kosong, layanan gagal, atau respons tidak valid, pembuatan laporan tetap berjalan dan nilai AI dibiarkan kosong. Aplikasi mobile mengambil hasil AI dari API Laravel; mobile tidak memanggil FastAPI.

## Keterbatasan

- RealWaste berasal dari satu fasilitas landfill; pembagian acak per gambar dapat menempatkan foto dengan kondisi pengambilan serupa di split yang berbeda.
- Akurasi test tidak menjamin performa pada foto ponsel Indonesia, pencahayaan, latar, atau jenis sampah yang berbeda. Evaluasi independen pada foto lokal belum digantikan oleh angka test ini.
- Klasifikasi hanya mencakup tiga kelas yang dilatih.
- Pernyataan lisensi sumber saling berbeda; konfirmasi langsung dengan pemilik dataset diperlukan sebelum penggunaan yang bergantung pada hak komersial.
