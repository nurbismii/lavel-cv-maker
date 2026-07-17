# KTP Location Preview Design

## Tujuan

Menampilkan lokasi administratif dari input pengguna sebagai bagian dari Alamat KTP pada preview CV HRIS.

## Perilaku

- `ktp_address` ditampilkan dengan label **Alamat KTP** bila alamat KTP dan domisili berbeda.
- Nilai lokasi yang dibentuk dari `village_name`, `district_name`, `regency_name`, dan `province_name` ditampilkan pada baris berikutnya dalam blok Alamat KTP.
- `address` ditampilkan dengan label **Alamat Domisili** tanpa tambahan lokasi administratif.
- Seluruh teks alamat dan lokasi berasal dari input profil pengguna; tidak ada alamat atau lokasi yang di-hardcode.
- Jika alamat KTP tidak ditampilkan, lokasi administratif tidak ditampilkan sebagai baris mandiri.

## Implementasi

- Ubah `CvPreviewDataService::addresses()` agar lokasi digabung dengan nilai alamat KTP ketika blok KTP ditampilkan.
- Pertahankan normalisasi alamat dan kondisi pencegahan duplikasi yang ada.
- Sesuaikan unit test untuk memverifikasi urutan dan nilai output alamat.

## Validasi

- Tambahkan test yang gagal lebih dahulu untuk memastikan lokasi berada di bawah alamat KTP.
- Jalankan unit test `CvPreviewDataServiceTest` setelah implementasi.
