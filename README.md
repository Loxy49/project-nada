# NADA (Nota Arsip Digital Akuntansi)

Sistem digital untuk pencatatan dan pencetakan nota kas kecil (petty cash) —
menggantikan proses pembuatan nota manual oleh tim accounting saat karyawan
membeli kebutuhan kecil kantor (parkir, konsumsi, ATK, pulsa/token, dll) yang
tidak disertai nota resmi dari penjual. Tujuannya memudahkan pengarsipan dan
rekap bulanan tim accounting.

Dibangun dengan **Laravel 12** (PHP) dan **MySQL**.

## Fitur Utama

**Pencatatan & cetak**

- Nota multi-item: tiap item punya kategori, keterangan, kuantitas, dan harga satuan
- Field khusus **No. Pelanggan & SN** per item untuk transaksi pulsa/token/PDAM (ikut tercetak dan masuk Excel)
- Metode bayar (Tunai, Transfer Bank, QRIS, Kartu Debit/Kredit, Lainnya) dan foto bukti opsional (gambar, maks. 2 MB; foto besar dari HP otomatis diperkecil di browser)
- **Foto bukti sebagai arsip**: disimpan di penyimpanan private (tidak bisa dibuka lewat URL tanpa login), dilihat dari Daftar Nota atau halaman detail nota, dan **tidak ikut tercetak** di nota
- Nomor transaksi otomatis, format `PN-YYYYMMDD-XXX`; tidak kembar walau ada nota yang dihapus
- Cetak nota dalam 2 gaya (Formal dan Struk) dan 2 orientasi (potret/lanskap), dilengkapi nominal terbilang
- Template kop nota yang bisa dikustomisasi admin (nama toko, logo, alamat) tanpa ubah kode

**Rekap & arsip**

- Daftar nota dengan pencarian & filter (kategori, bulan)
- **Jejak audit**: setiap nota dibuat, diubah (kondisi sebelum & sesudah), dihapus, atau dipulihkan tercatat lengkap dengan siapa dan kapan; admin melihatnya di halaman nota dan di menu **Log Aktivitas**
- **Hapus nota = soft delete**: admin wajib mengisi alasan, nota bisa dipulihkan dari Daftar Nota → Nota terhapus, dan nomornya tidak dipakai lagi
- Dashboard: pengeluaran per kategori bulan ini dan tren bulanan
- Ekspor rekap bulanan ke **Excel (.xlsx)** (satu baris per item) dan **PDF**; rekap per kategori dihitung dari item, jadi nota berisi beberapa kategori terbagi dengan benar
- **Kunci periode**: admin menutup bulan yang sudah direkap; nota di bulan itu tidak bisa ditambah, diubah, atau dihapus oleh siapa pun sampai kuncinya dibuka dengan alasan yang tercatat

**Akun & keamanan**

- Login dan lupa/reset password. **Tidak ada registrasi publik**: akun dibuat admin
- Dua peran: karyawan dan admin (accounting)
- Akun tidak dihapus, tapi **dinonaktifkan** (nota yang pernah dibuat tetap utuh); akun nonaktif tidak bisa login dan sesinya langsung diputus
- Tes otomatis (`php artisan test`) dan perintah pemeriksa keamanan (`php artisan nada:cek-keamanan`)

**Tampilan**

- **Mode terang & gelap**: tombol di samping avatar profil; pilihan disimpan di browser, dan bila belum pernah dipilih mengikuti pengaturan perangkat
- Halaman cetak dan preview template selalu terang karena menggambarkan kertas

## Hak Akses

|                                       | Karyawan                                     | Admin                             |
| ------------------------------------- | -------------------------------------------- | --------------------------------- |
| Buat nota & cetak                     | ✓                                            | ✓                                 |
| Lihat daftar nota                     | hanya nota sendiri                           | semua nota                        |
| Edit nota                             | nota sendiri, sampai 7 hari setelah dibuat\* | semua nota                        |
| Hapus & pulihkan nota                 | ✗                                            | ✓ (alasan wajib, bisa dipulihkan) |
| Dashboard                             | ringkasan sendiri                            | ringkasan seluruh nota            |
| Kategori, Template Nota, Ekspor Rekap | ✗                                            | ✓                                 |
| Kunci Periode                         | ✗                                            | ✓                                 |
| Kelola Pengguna                       | ✗                                            | ✓                                 |
| Log Aktivitas (jejak audit)           | ✗                                            | ✓                                 |

\* Batas hari edit karyawan diatur lewat `NOTA_EDIT_KARYAWAN_HARI` di `.env` (default 7). Setiap perubahan tercatat di jejak audit.

Catatan: nota di **periode yang sudah dikunci** tidak bisa diubah, dihapus, atau dipulihkan oleh siapa pun, termasuk admin (karyawan melihatnya sebagai "Sudah diarsipkan"). Cetak ulang tetap bisa.

## Kebutuhan Sistem

- PHP >= 8.2 dengan ekstensi `mbstring`, `pdo_mysql`, `fileinfo`, dan `gd`; untuk menjalankan tes perlu juga `pdo_sqlite` dan `sqlite3`
- Composer
- MySQL (bisa pakai Laragon/XAMPP)
- Node.js & npm (untuk compile asset CSS/JS)

## Cara Instalasi

1. **Clone project ini**, lalu masuk ke foldernya lewat terminal:

    ```
    git clone https://github.com/Loxy49/nada-notadigital.git
    cd nada-notadigital
    ```

2. **Install dependency PHP:**

    ```
    composer install
    ```

3. **Install dependency JavaScript:**

    ```
    npm install
    npm run build
    ```

4. **Salin file environment:**

    ```
    copy .env.example .env
    ```

    (di Mac/Linux pakai `cp .env.example .env`)

5. **Generate application key:**

    ```
    php artisan key:generate
    ```

6. **Buat database MySQL baru** (misalnya lewat HeidiSQL/phpMyAdmin), namanya bebas — contoh `nada`.

7. **Sesuaikan koneksi database di `.env`:**

    ```
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=nada
    DB_USERNAME=root
    DB_PASSWORD=
    ```

8. **Jalankan migration dan seeder** (membuat semua tabel dan 5 kategori awal: Parkir, Makan / konsumsi, Alat tulis kantor, Transportasi, Lain-lain):

    ```
    php artisan migrate --seed
    ```

9. **Buat symbolic link storage** (wajib, supaya logo template nota bisa tampil; foto bukti tidak memerlukannya karena disimpan private dan ditampilkan lewat aplikasi):

    ```
    php artisan storage:link
    ```

10. **Buat akun admin pertama** (password diketik lewat prompt, tidak tersimpan di riwayat terminal):

    ```
    php artisan nada:buat-admin
    ```

11. **Jalankan server:**
    ```
    php artisan serve
    ```
    Buka `http://127.0.0.1:8000` dan login dengan akun admin tadi. Akun karyawan dibuat lewat menu **Admin → Pengguna**.

## Memperbarui Aplikasi

Setelah mengambil versi terbaru (`git pull`), jalankan:

```
composer install
php artisan migrate
```

`migrate` juga memindahkan foto bukti lama dari `storage/app/public` ke penyimpanan private `storage/app/private`. Isi kolom di database tidak berubah.

## Cara Pakai Singkat

**Karyawan:** login → **Buat Nota** → isi tanggal, item (kategori, keterangan, qty, harga; No. Pelanggan/SN kalau pulsa/token), metode bayar, dan foto bukti bila ada → simpan → **Cetak**. Salah input? Nota sendiri bisa diedit sampai 7 hari setelah dibuat, selama belum diarsipkan accounting.

**Admin / accounting:**

1. Pantau nota di **Daftar Nota** dan **Dashboard**; periksa siapa mengubah atau menghapus apa di **Log Aktivitas**.
2. Akhir bulan, unduh rekap **Excel/PDF** (menu Ekspor Rekap, atau tombol di halaman Kunci Periode).
3. Buka **Kunci Periode** dan kunci bulan yang sudah direkap supaya notanya tidak bisa berubah lagi.
4. Perlu koreksi? Buka kunci bulan itu (alasan wajib diisi, tercatat di riwayat), perbaiki, lalu kunci lagi.

Akun yang tidak lagi bekerja: **Admin → Pengguna → Edit**, matikan centang "Akun aktif". Jangan dihapus. Admin tidak bisa menonaktifkan atau menurunkan akunnya sendiri.

## Konfigurasi Email (opsional)

Untuk fitur "Lupa Kata Sandi" agar benar-benar mengirim email (bukan hanya tercatat di
`storage/logs/laravel.log`), atur `.env` bagian `MAIL_*` dengan SMTP asli (Gmail App
Password, Mailtrap, dll). Detail lengkap ada di komentar `.env.example`.

> **Pastikan alamat email tiap akun benar-benar milik penggunanya.** Tautan reset password dikirim ke alamat itu; siapa pun yang mengendalikan kotak suratnya bisa mengambil alih akun. Jangan pakai alamat generik seperti `admin@gmail.com`.

## Keamanan & Rilis ke Server

Sebelum dipakai sungguhan, atur `.env` di server:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://alamat-tetap-kamu
SESSION_SECURE_COOKIE=true
```

Lalu jalankan pemeriksa:

```
php artisan nada:cek-keamanan
```

Perintah ini menandai hal yang masih berbahaya (debug menyala, URL ngrok/HTTP, tidak ada admin aktif, akun contoh `test@example.com` masih ada, dll) beserta cara memperbaikinya. Yang tidak bisa dicek otomatis: password SMTP sudah diganti kalau pernah dibagikan, dan **backup database rutin**.

Jangan pernah membagikan atau meng-upload file `.env` (berisi password database dan email). File itu sudah ada di `.gitignore`, tapi bisa ikut bocor kalau project dikirim sebagai zip manual.

## Perintah Artisan Khusus

| Perintah                        | Fungsi                                                                                                |
| ------------------------------- | ----------------------------------------------------------------------------------------------------- |
| `php artisan nada:buat-admin`   | Buat akun (default admin) lewat terminal; dipakai untuk admin pertama atau kalau semua admin terkunci |
| `php artisan nada:cek-keamanan` | Periksa pengaturan keamanan dasar sebelum aplikasi dipakai sungguhan                                  |

## Menjalankan Tes

Ada dua jenis tes:

```
php artisan test     # tes PHP (fitur, hak akses, database)
npm run test:js      # tes JavaScript kompres foto di browser (butuh npm install)
```

Keduanya juga dijalankan otomatis oleh GitHub Actions di setiap pull request dan setiap push ke `main`.

Tes PHP memakai SQLite in-memory (tidak menyentuh database MySQL-mu). Mencakup pembuatan nota & nomor unik, rekap per kategori, cetak, ekspor, kunci periode, soft delete & jejak audit, aturan edit karyawan, pengelolaan pengguna, upload & akses foto bukti, tombol tema, dan pemeriksa keamanan.

Kalau muncul error `could not find driver` atau `"mbstring" extension is not available`, berarti PHP yang terpanggil belum mengaktifkan ekstensi tersebut. Cek dengan `php -m`, lalu aktifkan baris `extension=pdo_sqlite`, `extension=sqlite3`, dan `extension=mbstring` di `php.ini`. Kalau di komputer ada beberapa PHP (misalnya XAMPP dan instalasi lain), pastikan yang dipakai adalah yang benar dengan `where php`, atau jalankan langsung, misalnya `C:\xampp\php\php.exe artisan test`.

## Struktur Folder Penting

```
app/Http/Controllers/    → NotaController, KategoriController, PengaturanController, DashboardController,
                           RekapExportController, PeriodeController, UserController, LogNotaController
app/Http/Middleware/     → PastikanAkunAktif (memutus sesi akun yang dinonaktifkan)
app/Console/Commands/    → BuatAdmin, CekKeamanan
app/Models/              → Nota, NotaItem, NotaLog, Kategori, Pengaturan, PeriodeKunci, User
config/pettynota.php     → pengaturan aplikasi (batas hari edit karyawan)
database/migrations/     → riwayat perubahan struktur database
resources/views/         → tampilan (auth, dashboard, nota, kategori, pengaturan, pengguna, periode, lognota, rekap)
resources/views/components/ikon.blade.php → ikon garis yang dipakai di seluruh tampilan (<x-ikon nama="cetak" />)
routes/web.php           → routing aplikasi
tests/Feature/           → tes otomatis
```

## Akun Uji Coba

Tidak ada akun bawaan (sengaja, demi keamanan). Buat admin dengan `php artisan nada:buat-admin`, lalu tambah karyawan lewat menu **Admin → Pengguna**.

## Batasan yang Diketahui

- Foto bukti yang diganti lewat edit nota langsung dihapus dari penyimpanan (jejak audit hanya mencatat nama filenya). Foto pada nota yang dihapus (soft delete) tetap disimpan supaya nota yang dipulihkan masih punya bukti.
- Foto bukti dan logo ada di folder `storage`, tidak ikut tersimpan di git. Sertakan folder itu dalam backup rutin bersama database.
- Pilihan tema terang/gelap disimpan per browser, jadi tidak ikut pindah ke perangkat lain.
- Mode gelap memetakan warna inline yang sudah dipakai di halaman. Halaman baru sebaiknya memakai variabel warna (`var(--text)`, `var(--muted)`, dll) agar ikut berubah.
- Jejak audit mencatat perubahan nota, bukan setiap kali nota dilihat atau dicetak.
- Karyawan yang masih dalam batas hari edit bisa mengubah tanggal nota ke bulan lain yang belum dikunci; perubahan itu tercatat di jejak audit.
- Peran `admin` dipakai bersama oleh accounting dan pengelola sistem; belum ada peran `accounting` terpisah.
