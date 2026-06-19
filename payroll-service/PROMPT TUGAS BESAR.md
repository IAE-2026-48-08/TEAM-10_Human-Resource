# Prompt Engineering Log — Tugas Besar IAE
Nama: Satrio
NIM: 102022400173
Akun: warga34@ktp.iae.id
Service: Payroll Service (Human Resource)
Tanggal: 20 Juni 2026

## Konteks Awal

Sebelum mulai prompting, saya melakukan analisis mandiri terhadap kebutuhan Tugas Besar. Ada tiga hal utama yang harus diselesaikan: pertama, menggabungkan tiga mini-service (Data Karyawan, Absensi, Payroll) ke dalam satu infrastruktur Docker Compose. Kedua, membungkus semua service di balik Nginx API Gateway sehingga tidak ada service yang bisa diakses langsung dari luar. Ketiga, memastikan alur bisnis end-to-end berjalan otomatis dari pengambilan data karyawan dan absensi hingga pemrosesan gaji, audit SOAP, dan broadcast RabbitMQ.

Saya juga mengidentifikasi bahwa Payroll Service perlu dimodifikasi agar proses penggajian tidak lagi menerima input manual seperti base_salary dan data absensi, melainkan mengambil data tersebut secara otomatis dari Employee Service dan Attendance Service melalui REST API internal.

## Sesi Prompting

### Prompt 1 — Analisis Arsitektur Integrasi

**Konteks analisis saya sebelum prompt:**
Saya perlu merancang arsitektur yang memungkinkan ketiga service berjalan dalam satu network Docker tanpa expose port masing-masing service ke publik. Hanya Nginx Gateway yang boleh diakses dari luar. Saya juga perlu memastikan komunikasi antar service bisa berjalan via hostname Docker internal.

**Prompt:**
> Saya sedang mengintegrasikan tiga Laravel service (Employee, Attendance, Payroll) ke dalam satu infrastruktur Docker Compose dengan Nginx sebagai API Gateway. Semua service harus berada dalam satu Docker network privat dan hanya bisa diakses melalui Gateway di port 8000. Tolong bantu saya merancang arsitektur docker-compose.yml dan konfigurasi Nginx yang tepat, termasuk bagaimana cara service Payroll bisa hit service Employee dan Attendance secara internal menggunakan hostname Docker.

**Hasil & keputusan arsitektur:**
- Semua service berada dalam network `hr-network` yang terisolasi
- Nginx listen di port 80 dan di-expose ke host sebagai port 8000
- Routing Nginx: `/api/v1/employees` → `employee-web:80`, `/api/v1/absensi` → `absensi-app:80`, `/api/v1/payrolls` → `payroll-app:8000`
- Komunikasi internal antar service menggunakan hostname Docker (bukan localhost)
- Fallback route mengembalikan 404 JSON jika endpoint tidak ditemukan

**Konfigurasi Nginx yang dihasilkan:**
```nginx
server {
    listen 80;

    location /api/v1/employees {
        proxy_pass http://employee-web:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /api/v1/absensi {
        proxy_pass http://absensi-app:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /api/v1/payrolls {
        proxy_pass http://payroll-app:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location / {
        return 404 '{"status":"error","message":"Gateway Route Not Found"}';
    }
}
```

### Prompt 2 — Modifikasi Payroll Service untuk Integrasi Otomatis

**Konteks analisis saya sebelum prompt:**
Pada implementasi awal, PayrollController menerima input manual seperti base_salary, total_present, dan total_absent langsung dari request body. Ini tidak cocok untuk sistem terintegrasi karena data tersebut seharusnya diambil otomatis dari Employee Service dan Attendance Service. Saya perlu merefactor PayrollController agar hanya menerima employee_id, period_month, period_year, dan bonus, lalu fetch data sisanya secara internal.

**Prompt:**
> PayrollController saya saat ini masih menerima input manual base_salary dan data absensi dari request body. Saya ingin merefactor-nya agar hanya menerima employee_id, period_month, period_year, dan bonus, kemudian secara otomatis hit Employee Service di http://employee-web:80/api/v1/employees/{id} untuk ambil base_salary dan hit Attendance Service di http://absensi-app:80/api/v1/absensi/{employee_id} untuk ambil data absensi bulan tersebut. Tolong buatkan implementasinya menggunakan Laravel Http facade dengan error handling yang proper jika salah satu service tidak tersedia.

**Hasil:**
- PayrollController direfactor dengan dua Http::get() call ke service internal
- Jika Employee Service tidak merespons, return 503 dengan pesan yang informatif
- Jika Attendance Service tidak merespons, kalkulasi tetap jalan dengan nilai default
- Data base_salary diambil dari response Employee Service
- Data total_present dan total_absent diambil dari response Attendance Service berdasarkan filter bulan dan tahun

### Prompt 3 — Setup Repository Kelompok dan Git Workflow

**Konteks analisis saya sebelum prompt:**
Kami perlu satu repository kelompok yang menampung ketiga service sekaligus docker-compose.yml dan konfigurasi Nginx. Saya perlu memastikan workflow Git yang benar agar tidak terjadi konflik ketika tiga orang push secara bersamaan.

**Prompt:**
> Saya perlu setup repository kelompok di GitHub untuk menampung tiga service Laravel (employee-service, attendance-service, payroll-service) beserta docker-compose.yml dan konfigurasi Nginx API Gateway. Tolong jelaskan struktur folder yang tepat dan workflow Git yang aman untuk tim tiga orang agar tidak terjadi konflik, termasuk cara yang benar untuk pull sebelum push dan menangani conflict pada file seperti composer.lock.

**Hasil — struktur repo kelompok:**
```
TEAM-10_Human-Resource/
├── docker-compose.yml
├── nginx/
│   └── gateway.conf
├── employee-service/
├── attendance-service/
└── payroll-service/
```

**Workflow yang disepakati tim:**
- Selalu `git pull origin main` sebelum mulai kerja
- Jika ada conflict di `composer.lock`, gunakan `git checkout -- composer.lock` untuk reset ke versi remote
- Gunakan `git add <nama-file>` spesifik, bukan `git add .` sembarangan
- Commit message menggunakan konvensi: `feat:`, `fix:`, `docs:`

### Prompt 4 — Debugging Docker Build Error

**Konteks analisis saya sebelum prompt:**
Saat pertama kali menjalankan `docker-compose up -d --build`, build gagal di step composer install dengan exit code 4. Saya perlu mengidentifikasi apakah ini masalah memory atau ada dependency yang tidak sinkron antara composer.json dan composer.lock.

**Prompt:**
> docker-compose build payroll-app gagal dengan exit code 4 di step composer install. Saya sudah coba tambah flag --no-dev --no-interaction --ignore-platform-reqs tapi masih gagal. Tolong bantu saya debug dengan cara melihat full build log dan identifikasi root cause-nya, apakah ini masalah memory atau ada package yang tidak sinkron di composer.lock.

**Hasil debugging:**
Dari full build log ditemukan root cause yang sebenarnya bukan masalah memory, melainkan package `firebase/php-jwt` ada di `composer.json` tapi tidak ada di `composer.lock` karena file lock tidak di-update setelah package ditambahkan secara manual.

**Fix:**
```bash
cd payroll-service
composer require firebase/php-jwt
```

Perintah ini otomatis update `composer.lock` dan setelah itu build berhasil.

### Prompt 5 — Pengujian End-to-End via API Gateway

**Konteks analisis saya sebelum prompt:**
Setelah semua container berhasil jalan, saya perlu memastikan alur bisnis end-to-end berjalan mulus: request masuk melalui Nginx Gateway, diteruskan ke Payroll Service, Payroll Service fetch data ke Employee dan Attendance Service secara internal, kemudian proses SSO, SOAP, dan RabbitMQ berjalan berurutan.

**Prompt:**
> Semua container sudah jalan dengan docker-compose up -d --build. Sekarang saya ingin menguji alur end-to-end melalui API Gateway di port 8000. Tolong buatkan panduan pengujian bertahap di Postman mulai dari: (1) verifikasi gateway routing ke masing-masing service, (2) pengujian proses payroll otomatis yang fetch data dari Employee dan Attendance Service secara internal, (3) verifikasi bahwa SSO, SOAP, dan RabbitMQ terpanggil dengan benar dalam satu request.

**Hasil pengujian:**

| Tahap | Endpoint via Gateway | Status | Keterangan |
|-------|---------------------|--------|------------|
| Verifikasi Employee | GET :8000/api/v1/employees | 200 OK | Data karyawan berhasil diambil |
| Verifikasi Absensi | GET :8000/api/v1/absensi | 200 OK | Data absensi berhasil diambil |
| End-to-End Payroll | POST :8000/api/v1/payrolls/process | 201 Created | SSO, SOAP, RabbitMQ semua terpanggil |

**Catatan debugging saat pengujian:**
Saat pertama kali test endpoint payroll melalui gateway, muncul error 404. Setelah dicek ternyata path yang digunakan salah, seharusnya `/api/v1/payrolls/process` bukan `/api/v1/payroll/process` (tanpa 's'). Setelah path diperbaiki, request berhasil dan response 201 dengan `sso_connected: true` dan `soap_audited: true` berhasil didapat.

## Ringkasan Capaian Tugas Besar

| Komponen | Status | Bukti |
|----------|--------|-------|
| API Gateway Nginx | Selesai | Semua route terkonfigurasi, fallback 404 aktif |
| Docker Compose Integrasi | Selesai | Semua container jalan dalam satu network |
| End-to-End Business Flow | Selesai | Payroll otomatis fetch Employee dan Absensi |
| SSO + SOAP + RabbitMQ | Selesai | Terpanggil dalam satu request POST /payrolls/process |
| Git Contribution | Selesai | Commit terdokumentasi di repo kelompok |

## Refleksi

Pendekatan yang paling efektif dalam sesi prompting Tugas Besar ini adalah selalu melakukan analisis mandiri terlebih dahulu sebelum membuat prompt. Dengan memahami dulu apa yang ingin dicapai dan kendala apa yang mungkin muncul, prompt yang dihasilkan jauh lebih spesifik dan jawaban yang didapat langsung applicable tanpa perlu banyak iterasi. Debugging juga lebih efisien karena prompt menyertakan full error log sehingga root cause bisa diidentifikasi dengan tepat, bukan hanya gejala permukaannya saja.
