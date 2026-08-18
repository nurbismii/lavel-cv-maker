# Desain Reset Password Vitae dan V-People

## Tujuan

Menyediakan alur lupa/reset password berbasis email untuk akun Vitae. Setelah reset berhasil, hash password yang sama harus tersimpan pada akun lokal dan akun V-People yang terhubung. Reset ditolak apabila akun tidak terhubung ke V-People, akun V-People tidak ditemukan, atau salah satu pembaruan gagal.

## Ruang Lingkup

- Menambahkan tautan lupa password pada halaman login.
- Menambahkan halaman permintaan tautan reset dengan input email.
- Mengirim tautan reset menggunakan Laravel Password Broker.
- Menambahkan halaman pengaturan password baru.
- Memperbarui password lokal dan `vpeople.users.password` sebagai satu operasi logis.
- Memberikan feedback berhasil, validasi gagal, dan kegagalan integrasi yang aman.
- Menambahkan pengujian otomatis untuk alur utama dan kegagalan sinkronisasi.

Perubahan password dari halaman profil, reset menggunakan NIK, queue sinkronisasi, dan perubahan sistem autentikasi V-People berada di luar ruang lingkup.

## Keputusan Utama

1. Pengguna meminta reset hanya menggunakan email.
2. Respons permintaan reset tidak mengungkapkan apakah email terdaftar.
3. Akun lokal wajib memiliki NIK V-People terenkripsi yang valid.
4. Akun V-People wajib ditemukan berdasarkan NIK dan emailnya harus sama dengan email akun lokal.
5. Password baru di-hash satu kali dan hash yang sama ditulis ke kedua sistem.
6. Reset dianggap gagal seluruhnya jika V-People atau database lokal gagal diperbarui.
7. Token reset hanya dikonsumsi setelah seluruh operasi berhasil.
8. Detail kegagalan integrasi masuk ke log tanpa password, token, atau hash password.

## Pendekatan

Fitur menggunakan Laravel Password Broker yang sudah tersedia di Laravel 8 untuk membuat, menyimpan, memvalidasi, dan menghapus token. Sinkronisasi password ditempatkan pada layanan V-People agar controller tetap menangani orkestrasi HTTP dan layanan tersebut menangani identitas akun eksternal serta pembaruan hash.

Karena akun lokal dan V-People berada pada koneksi database berbeda, transaksi database biasa tidak dapat menjamin atomicity lintas koneksi. Implementasi memakai rollback kompensasi:

1. Validasi token dan seluruh input.
2. Resolusi akun lokal dan akun V-People sebelum ada perubahan.
3. Simpan hash lama kedua akun di memori untuk kebutuhan pemulihan.
4. Buat satu hash password baru.
5. Perbarui akun V-People dan akun lokal dalam proses terkontrol.
6. Jika pembaruan atau commit gagal, kembalikan setiap sisi yang sudah berubah ke hash sebelumnya.
7. Hapus token hanya setelah kedua sisi berhasil.

Pendekatan ini menangani kegagalan aplikasi dan database normal. Masih ada celah sangat kecil jika proses server berhenti tepat di antara commit dua koneksi. Distributed transaction/XA tidak dipilih karena menambah ketergantungan infrastruktur dan kompleksitas operasional yang tidak sebanding dengan cakupan fitur.

## Komponen

### Routing

Route guest mencakup:

- halaman permintaan tautan reset;
- endpoint pengiriman tautan dengan throttle;
- halaman form password baru yang menerima token;
- endpoint penyelesaian reset dengan throttle.

Nama route mengikuti konvensi Laravel agar URL pada notifikasi reset bawaan dapat dibuat tanpa override yang tidak perlu.

### Controller autentikasi

Controller terpisah menangani permintaan tautan dan penyelesaian reset. Controller tidak melakukan query langsung ke V-People. Semua kegagalan memberikan pesan yang dapat ditindaklanjuti tanpa membocorkan detail akun atau infrastruktur.

### Layanan akun V-People

`VPeopleAccountService` diperluas dengan operasi untuk:

- menemukan akun V-People yang tepat dari user lokal;
- menolak user tanpa NIK terhubung atau NIK yang tidak dapat didekripsi;
- menolak akun yang tidak ditemukan atau email yang tidak cocok;
- memperbarui password menggunakan hash yang disediakan;
- memastikan tepat satu baris diperbarui;
- mengembalikan hash lama saat kompensasi diperlukan.

Layanan tidak menerima atau mencatat password plaintext apabila controller sudah menghasilkan hash.

### View dan feedback

View baru mengikuti layout auth, komponen form, password toggle, loading state, dan gaya notifikasi yang sudah digunakan proyek. Halaman login mendapat tautan lupa password. Pesan permintaan email selalu netral. Form reset menampilkan error token, validasi password, dan gangguan sinkronisasi secara aman.

### Notifikasi email

User lokal menggunakan notifikasi reset password Laravel. URL mengarah ke route form reset dan membawa token serta email. Masa berlaku token mengikuti broker `users` di `config/auth.php`.

## Alur Data

### Permintaan tautan

1. Pengguna memasukkan email.
2. Sistem menjalankan Password Broker untuk mengirim tautan jika user ditemukan.
3. Sistem selalu memberikan respons netral pada browser.
4. Endpoint dibatasi throttle untuk mengurangi enumeration dan spam.

### Penyelesaian reset

1. Pengguna mengirim token, email, password, dan konfirmasi password.
2. Password Broker memastikan token dan user valid.
3. Sistem memvalidasi keterhubungan user dengan akun V-People.
4. Sistem membuat satu hash baru dan menulisnya ke kedua database.
5. Jika salah satu sisi gagal, sistem menjalankan kompensasi dan mengembalikan respons gagal; token tidak dikonsumsi.
6. Jika keduanya berhasil, sistem memperbarui remember token lokal, menghapus token reset, dan mengarahkan pengguna ke login.

## Penanganan Error dan Keamanan

- Email permintaan reset memakai format email valid dan normalisasi yang konsisten.
- Password minimal 8 karakter dan wajib dikonfirmasi, mengikuti aturan registrasi yang sudah ada.
- Endpoint permintaan dan penyelesaian reset memakai throttle.
- Pesan permintaan tautan tidak membedakan email terdaftar dan tidak terdaftar.
- Akun V-People dicocokkan menggunakan NIK terhubung dan email untuk mencegah pembaruan akun yang salah.
- Query pembaruan memverifikasi jumlah baris yang diperbarui.
- Password plaintext, token reset, hash password, dan NIK plaintext tidak ditulis ke log.
- Log mencatat user lokal, jenis kegagalan, dan exception untuk kebutuhan operasional.
- Gangguan V-People tidak menyebabkan fallback ke reset lokal saja.

## Strategi Testing

Pengujian feature dan unit harus membuktikan:

- halaman lupa password dapat diakses oleh guest dan tertaut dari login;
- email valid menghasilkan notifikasi reset untuk user terdaftar;
- respons email tidak mengungkap user yang tidak terdaftar;
- throttle diterapkan pada endpoint sensitif;
- token valid menampilkan form reset;
- token salah atau kedaluwarsa ditolak;
- password lemah atau konfirmasi berbeda ditolak;
- user tanpa NIK V-People ditolak tanpa mengubah password lokal;
- NIK tidak valid, akun V-People tidak ditemukan, dan email tidak cocok ditolak;
- kegagalan update V-People tidak mengubah password lokal dan tidak mengonsumsi token;
- kegagalan lokal setelah update eksternal memulihkan hash lama V-People;
- reset berhasil menyimpan hash identik pada kedua database;
- password baru dapat dipakai login dan password lama tidak lagi valid;
- log kegagalan tidak mengandung password, token, atau hash.

Semua pengujian fitur baru ditulis dengan siklus red-green-refactor sebelum kode produksi.

## Dampak Production dan Rollback

Fitur memakai tabel `password_resets` yang sudah ada sehingga tidak membutuhkan migration baru. Konfigurasi mail harus valid agar tautan dapat terkirim. Koneksi V-People harus mempunyai izin `SELECT` dan `UPDATE` pada `users`.

Rollback aplikasi dapat dilakukan dengan menghapus route, controller, view, dan metode layanan baru. Tidak ada perubahan skema yang perlu dibatalkan. Hash password yang sudah berhasil disinkronkan tidak perlu dikembalikan karena tetap valid pada kedua sistem.
