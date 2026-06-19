# RESUME KONTRIBUSI INDIVIDU
## Tugas Besar — Sistem Human Resource & Penggajian Karyawan (TEAM-10)

**Nama:** Ramdani Cahyo Bagaskara
**NIM:** 102022400319
**Kelas:** SI-48-08
**Service:** Service 2 — Absensi Karyawan | **Branch:** `Service-Absensi-bagas`

## Kontribusi yang Dikerjakan

Berikut rangkuman pekerjaan yang saya selesaikan dalam pengembangan Layanan Absensi Karyawan sebagai bagian dari sistem HR dan Penggajian Kelompok 10.

**Inisialisasi & Basis Data** Membangun proyek Laravel 12 dari awal, mengonfigurasi SQLite sebagai penyimpan data, merancang skema tabel `absensis` via migrasi, dan membuat skrip pengisi 50 data uji coba.
**REST API** Mengembangkan tiga jalur akses utama (`GET` semua data, `GET` per ID, `POST` tambah rekap) lengkap dengan validasi input, format respons standar IAE, dan pencegahan entri ganda.
**Keamanan & Autentikasi** Membuat lapisan penyaring permintaan yang mendukung dua cara verifikasi: token JWT dari peladen SSO (divalidasi via kumpulan kunci publik JWKS) dan kunci API berbasis NIM melalui header `X-IAE-KEY`. Data kunci publik disimpan sementara selama 10 menit agar tidak membebani SSO.
**Hak Akses Berbasis Peran** Menyusun tiga tingkatan peran (`admin`, `karyawan`, `warga`) dengan pembatasan bahwa hanya `admin` yang bisa menambahkan rekap absensi baru. Peran lain hanya bisa membaca data.
**Komunikasi Lintas Layanan** Mengintegrasikan pengecekan otomatis ke Layanan Data Karyawan setiap ada pengajuan absensi baru, guna memastikan karyawan yang bersangkutan benar-benar terdaftar di sistem.
**Audit SOAP** Membangun klien pengiriman catatan audit ke peladen terpusat menggunakan format pesan XML berstruktur ketat. Nomor tanda terima yang dikembalikan peladen diekstrak dan disimpan ke basis data lokal sebagai bukti transaksi.
**Notifikasi Antrean Pesan** Membuat modul penyebar pemberitahuan ke sistem antrean pesan RabbitMQ via HTTP setiap rekap absensi baru tersimpan, menggunakan kunci perutean `absensi.created` dalam format JSON.
**Token Antar-Mesin** Mengintegrasikan pengambilan token M2M dari SSO dan menyimpannya sementara selama 50 menit agar tidak perlu meminta token baru di setiap transaksi.
**Dokumentasi API** Menulis anotasi OpenAPI 3.0 langsung di dalam controller menggunakan paket `l5-swagger`, menghasilkan dokumentasi interaktif yang bisa diakses via Swagger UI.
**Kontainerisasi** Menyusun `Dockerfile`, skrip `docker-entrypoint.sh` untuk otomatisasi startup (migrasi, seeding, caching, perizinan), dan berkontribusi dalam konfigurasi `docker-compose.yml` tingkat tim.
**API Gateway** Mendaftarkan rute layanan absensi pada konfigurasi Nginx gateway dan memastikan seluruh header permintaan diteruskan dengan benar ke aplikasi di baliknya.