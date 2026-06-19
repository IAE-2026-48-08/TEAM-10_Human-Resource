# AI Prompting Log — Tugas 2
**BBK2HAB3 - Integrasi Aplikasi Enterprise**  
Service: Penggajian (Payroll Service) — Human Resource  
Nama: I Gede Satriya Pradnya Wiguna
Tools AI: Claude (Anthropic)

---

## Tahap 0 — Analisis Awal Tugas

Sebelum mulai coding, saya membaca dan menganalisis dokumen tugas secara menyeluruh. Beberapa poin kunci yang saya identifikasi:

1. **Rubrik penilaian** terbagi 4 kriteria: REST (40%), Swagger (20%), GraphQL (20%), Security (10%)
2. **Standard Integration Contract** mewajibkan response wrapper JSON konsisten dengan field `status`, `message`, `data`, `meta`
3. **Security** menggunakan API Key via header `X-IAE-KEY` dengan value NIM mahasiswa
4. **Dokumentasi** wajib menggunakan Swagger/OpenAPI (L5-Swagger untuk Laravel)
5. **GraphQL** minimal 1 query menggunakan Lighthouse
6. Service yang saya kerjakan adalah **Penggajian (Payroll)** dengan 3 endpoint wajib: GET semua, GET by ID, POST proses

Dari analisis tersebut, saya menyusun urutan pengerjaan: Setup → Database → REST API → Security → Swagger → GraphQL → Docker → Testing.

---

## Prompt 1 — Perencanaan Arsitektur Service

**Prompt saya ke AI:**
> Saya mendapat bagian **Payroll Service** dalam ekosistem Human Resource untuk mata kuliah Integrasi Aplikasi Enterprise. Berdasarkan dokumen tugas, service ini harus memenuhi:
> - Minimal 3 endpoint REST fungsional (GET all, GET by ID, POST process)
> - Response wrapper JSON konsisten sesuai Standard Integration Contract
> - API Key authentication via header `X-IAE-KEY`
> - Swagger/OpenAPI documentation via L5-Swagger
> - GraphQL minimal 1 query via Lighthouse
> - Berjalan di Docker
>
> Domain bisnis saya adalah penggajian karyawan berdasarkan data absensi bulanan. Logika kalkulasi: `net_salary = base_salary - deduction + bonus`, di mana `deduction = (base_salary / 22) * total_absent`.
>
> Tolong buatkan roadmap pengerjaan yang terstruktur beserta teknologi stack yang akan digunakan.

**Hasil yang didapat:**
AI memberikan roadmap 6 fase yang jelas: Setup Laravel → Database & Model → REST API + Middleware → Swagger → GraphQL → Finalisasi Docker. Checklist penilaian juga disertakan untuk memastikan semua kriteria rubrik terpenuhi.

---

## Prompt 2 — Setup Project & Struktur Database

**Prompt saya ke AI:**
> Lanjut ke implementasi. Saya menggunakan Laravel (PHP 8.2) dengan MySQL. Untuk tabel `payrolls`, saya butuh kolom yang merepresentasikan siklus penggajian lengkap:
> - Identitas karyawan: `employee_id`, `employee_name`
> - Periode: `period_month`, `period_year`
> - Komponen gaji: `base_salary`, `deduction`, `bonus`, `net_salary`
> - Data absensi: `total_present`, `total_absent`, `total_leave`
> - Status & audit: `status` (enum: draft/processed/paid), `processed_at`
>
> Buatkan migration lengkap, Model dengan `$fillable` dan `$casts` yang tepat, serta Seeder dengan minimal 3 data dummy yang kalkulasi gajinya realistis.

**Hasil yang didapat:**
AI memberikan migration dengan semua kolom yang dibutuhkan, Model Payroll dengan casting yang tepat untuk field decimal, dan PayrollSeeder dengan logika kalkulasi otomatis (`deduction` berdasarkan jumlah absen, `bonus` untuk kehadiran ≥20 hari).

---

## Prompt 3 — Debug Seeder Error

**Prompt saya ke AI:**
> Terjadi error saat menjalankan seeder:
> ```
> ErrorException: Undefined variable $employees
> at database\seeders\PayrollSeeder.php:16
> ```
> Sepertinya ada masalah scope variabel di dalam method `run()`. Tolong perbaiki.

**Hasil yang didapat:**
AI mengidentifikasi bahwa array `$employees` tidak terdefinisi di dalam scope method `run()`. Seeder diperbaiki dengan memindahkan deklarasi array ke dalam method yang benar. Seeder berhasil dijalankan dan mengisi 3 record payroll dummy.

---

## Prompt 4 — Implementasi REST API dengan Standard Integration Contract

**Prompt saya ke AI:**
> Sekarang saya akan membuat REST API layer. Berdasarkan Standard Integration Contract yang diwajibkan dosen, semua response harus menggunakan wrapper:
> ```json
> {
>   "status": "success|error",
>   "message": "...",
>   "data": {},
>   "meta": { "service_name": "Payroll-Service", "api_version": "v1" }
> }
> ```
>
> Buatkan `PayrollController` dengan 3 endpoint:
> 1. `GET /api/v1/payrolls` — list semua payroll dengan `meta.total`
> 2. `GET /api/v1/payrolls/{id}` — detail payroll, return 404 jika tidak ditemukan
> 3. `POST /api/v1/payrolls/process` — proses penggajian baru dengan validasi input lengkap dan kalkulasi otomatis
>
> Juga buatkan Middleware `CheckApiKey` yang memvalidasi header `X-IAE-KEY` dengan value dari config, return 401 jika tidak valid.

**Hasil yang didapat:**
AI memberikan `PayrollController` lengkap dengan ketiga method, response wrapper yang konsisten, validasi input di endpoint POST, dan kalkulasi `net_salary` otomatis. Middleware `CheckApiKey` juga dibuat dengan response error yang sesuai contract.

---

## Prompt 5 — Konfigurasi Routes & Middleware Laravel 12

**Prompt saya ke AI:**
> Di routes saya tidak ada `api.php`, hanya ada `console.php` dan `web.php`. Sepertinya ini karena Laravel 12 tidak generate `api.php` secara default. Bagaimana cara mengaktifkan API routes dan mendaftarkan middleware alias `check.api.key` di Laravel 12?

**Hasil yang didapat:**
AI menjelaskan bahwa di Laravel 12, API routes perlu diinstall terlebih dahulu dengan `php artisan install:api`. Setelah itu, registrasi middleware alias dilakukan di `bootstrap/app.php` menggunakan `$middleware->alias([...])` — berbeda dari Laravel versi sebelumnya yang menggunakan `Kernel.php`.

---

## Prompt 6 — Setup Docker tanpa Nginx

**Prompt saya ke AI:**
> Saya ingin containerize service ini dengan Docker. Saya mempertimbangkan dua pendekatan:
> 1. PHP-FPM + Nginx (lebih proper, mirip production)
> 2. PHP CLI dengan `php artisan serve` (lebih simpel)
>
> Untuk keperluan tugas dan development, saya pilih opsi 2 karena lebih simpel dan menghindari kompleksitas konfigurasi Nginx. Buatkan `Dockerfile` dengan `php:8.2-cli` dan `docker-compose.yml` dengan service `app` dan `db` (MySQL 8.0). Port mapping: `8080:8000`.

**Hasil yang didapat:**
AI memberikan `Dockerfile` menggunakan `php:8.2-cli` dengan CMD `php artisan serve --host=0.0.0.0 --port=8000`, dan `docker-compose.yml` dengan dua service tanpa Nginx. Konfigurasi `.env` untuk koneksi ke MySQL container juga disertakan.

---

## Prompt 7 — Debug Docker Build Issues

**Prompt saya ke AI:**
> Terjadi dua error berturut-turut saat build Docker:
>
> Error 1: `failed to read dockerfile: open Dockerfile: no such file or directory` padahal file sudah ada dan `Get-Content Dockerfile` menampilkan isinya dengan benar.
>
> Error 2: Setelah dibuat ulang via PowerShell, isi Dockerfile malah berisi command PowerShell itu sendiri:
> `[System.IO.File]::WriteAllText(...)` ikut masuk ke dalam file.
>
> Analisis saya: ini kemungkinan masalah encoding BOM dari PowerShell atau pipe yang salah. Bagaimana solusinya?

**Hasil yang didapat:**
AI mengkonfirmasi analisis bahwa PowerShell menyimpan file dengan BOM encoding yang tidak dikenali Docker. Solusi terbaik adalah membuat Dockerfile langsung dari VS Code dengan Save As "All Files" untuk menghindari ekstensi `.txt` dan masalah encoding.

---

## Prompt 8 — Debug Port Conflict

**Prompt saya ke AI:**
> Error saat `docker-compose up`:
> ```
> Bind for 0.0.0.0:8080 failed: port is already allocated
> ```
> Dari Docker Desktop terlihat ada container `payroll_nginx` yang statusnya orphan dari konfigurasi sebelumnya. Bagaimana membersihkannya?

**Hasil yang didapat:**
AI menyarankan `docker-compose down --remove-orphans` untuk menghapus container orphan sekaligus, kemudian `docker-compose up -d --build` ulang. Container berhasil berjalan tanpa konflik.

---

## Prompt 9 — Implementasi GraphQL dengan Lighthouse

**Prompt saya ke AI:**
> Sekarang implementasi GraphQL menggunakan Lighthouse. Saya butuh:
> 1. Schema yang merepresentasikan type `Payroll` dengan semua field dari model
> 2. Minimal 2 query: `payrolls` (list semua) dan `payroll(id)` (detail by ID)
> 3. Menggunakan directive bawaan Lighthouse (`@all`, `@find`, `@eq`) agar tidak perlu custom resolver
> 4. GraphQL Playground bisa diakses untuk testing
>
> Pastikan type `DateTime` juga didefinisikan karena model Payroll menggunakan field datetime.

**Hasil yang didapat:**
AI memberikan schema GraphQL lengkap dengan scalar `DateTime`, type `Payroll` dengan semua field, dan dua query menggunakan Lighthouse directives. Package `mll-lab/laravel-graphql-playground` diinstall untuk akses playground. GraphQL berhasil merespons query dengan data dari database.

---

## Prompt 10 — Debug GraphQL Timeout

**Prompt saya ke AI:**
> GraphQL Playground terbuka tapi menampilkan "Server cannot be reached". Dari log container terlihat request ke `/graphql` membutuhkan waktu 2-7 detik sebelum timeout. Endpoint REST masih berjalan normal.
>
> Dugaan saya: masalah koneksi database karena container di-rebuild ulang sehingga migration tidak ter-apply. Apakah benar?

**Hasil yang didapat:**
AI mengkonfirmasi dugaan tersebut. Setelah `docker-compose exec app php artisan migrate --seed` dijalankan, koneksi database terbentuk dan GraphQL berhasil merespons. Cache juga di-clear dengan `config:clear` dan `cache:clear` untuk memastikan konfigurasi terbaru terbaca.

---

## Prompt 11 — Implementasi Swagger dengan PHP Attributes

**Prompt saya ke AI:**
> Saya akan mengimplementasikan Swagger menggunakan L5-Swagger. Saya ingin menggunakan **PHP Attributes** (bukan DocBlock annotations) karena lebih modern dan type-safe di PHP 8.x.
>
> Yang saya butuhkan:
> 1. File terpisah `SwaggerInfo.php` untuk `@OA\Info` dan `@OA\SecurityScheme` (ApiKeyAuth dengan header `X-IAE-KEY`)
> 2. Anotasi untuk ketiga endpoint di `PayrollController` menggunakan format `#[OA\Get(...)]`, `#[OA\Post(...)]`
> 3. Request body untuk endpoint POST dengan semua field beserta contoh value
> 4. Response codes yang sesuai: 200, 201, 401, 404, 422

**Hasil yang didapat:**
AI memberikan implementasi lengkap dengan PHP Attributes. Namun terjadi beberapa iterasi debugging karena L5-Swagger v11 tidak mengenali `@OA\Info` di luar class. Solusinya adalah memindahkan anotasi ke dalam class `SwaggerInfo` dan menggunakan format PHP Attributes `#[OA\Info(...)]`. Setelah itu `php artisan l5-swagger:generate` berhasil dan Swagger UI dapat diakses dengan 3 endpoint terdokumentasi lengkap beserta fitur "Try it out".

---

## Ringkasan Hasil Akhir Tugas 2

| Kriteria | Bobot | Status | Bukti |
|----------|-------|--------|-------|
| REST Fungsional (3 endpoint di Docker) | 40% | ✅ | `GET /api/v1/payrolls`, `GET /api/v1/payrolls/{id}`, `POST /api/v1/payrolls/process` berjalan di `localhost:8080` |
| API Documentation (Swagger UI) | 20% | ✅ | Dapat diakses di `localhost:8080/api/documentation` dengan ApiKeyAuth |
| GraphQL Implementation | 20% | ✅ | Query `payrolls` dan `payroll(id)` berhasil di `localhost:8080/graphql-playground` |
| Security (API Key) | 10% | ✅ | Middleware `CheckApiKey` memvalidasi header `X-IAE-KEY` di semua endpoint |
| Repository | — | ✅ | Format nama `NIM_Payroll-Service` pada organisasi GitHub dosen |

**Total sesi prompting:** 11 prompt utama  
**Pendekatan:** Analisis rubrik terlebih dahulu → implementasi bertahap → debug iteratif  
**Tools AI yang digunakan:** Claude (Anthropic) — claude.ai
