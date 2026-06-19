# Service Absensi - Laravel Mini-Service

> **NIM:** 102022400319  
> **Service:** Absensi (Service 2)  
> **Ekosistem:** Human Resource - Penggajian Karyawan

## Deskripsi

Service Absensi adalah mini-service yang mengelola data rekap absensi bulanan karyawan. Service ini merupakan bagian dari ekosistem HR untuk penggajian karyawan, dan berkomunikasi dengan:
- **Service 1: Data Karyawan** — menyediakan data karyawan
- **Service 3: Penggajian** — menggunakan data absensi untuk perhitungan gaji

## Teknologi

| Komponen | Teknologi |
|----------|-----------|
| Framework | Laravel 12 (PHP 8.2) |
| Database | SQLite |
| API Docs | Swagger/OpenAPI (L5-Swagger) |
| GraphQL | Lighthouse PHP + GraphiQL Playground |
| Container | Docker + Docker Compose |
| Security | API Key via header `X-IAE-KEY` |

## Standard Integration Contract (IAE-T2)

- **Protokol:** HTTP/1.1
- **Format:** JSON (application/json, UTF-8)
- **Security:** API Key via header `X-IAE-KEY` dengan value NIM: `102022400319`
- **Response Wrapper:** `{ status, message, data, meta }`

## Instalasi & Menjalankan

### Lokal (tanpa Docker)

```bash
# Clone repository
git clone <repository-url>
cd <repository-folder>

# Install dependencies
composer install

# Copy environment
cp .env.example .env

# Generate app key
php artisan key:generate

# Buat database SQLite
touch database/database.sqlite

# Jalankan migrasi dan seeder
php artisan migrate --seed

# Generate Swagger docs
php artisan l5-swagger:generate

# Jalankan server
php artisan serve
```

### Docker

```bash
# Build dan jalankan
docker-compose up -d --build

# Akses di http://localhost:8000
```

## REST API Endpoints

Base URL: `http://localhost:8000/api/v1`

| Method | Endpoint | Deskripsi | Status Code |
|--------|----------|-----------|-------------|
| GET | `/api/v1/absensi` | Menampilkan seluruh rekap absensi | 200 |
| GET | `/api/v1/absensi/{id}` | Menampilkan rekap absensi per karyawan | 200, 404 |
| POST | `/api/v1/absensi` | Menambah/generate rekap absensi bulanan | 201, 422 |

### Header Wajib

```
X-IAE-KEY: 102022400319
```

### Contoh Request

#### GET Semua Absensi
```bash
curl -H "X-IAE-KEY: 102022400319" http://localhost:8000/api/v1/absensi
```

#### GET Absensi per Karyawan
```bash
curl -H "X-IAE-KEY: 102022400319" http://localhost:8000/api/v1/absensi/1
```

#### POST Tambah Absensi
```bash
curl -X POST http://localhost:8000/api/v1/absensi \
  -H "X-IAE-KEY: 102022400319" \
  -H "Content-Type: application/json" \
  -d '{
    "karyawan_id": 11,
    "nama_karyawan": "John Doe",
    "bulan": 6,
    "tahun": 2026,
    "total_hadir": 20,
    "total_sakit": 1,
    "total_izin": 1,
    "total_alpha": 0,
    "total_hari_kerja": 22
  }'
```

### Contoh Response (Success)
```json
{
  "status": "success",
  "message": "Data retrieved successfully",
  "data": [...],
  "meta": {
    "service_name": "Absensi-Service",
    "api_version": "v1"
  }
}
```

### Contoh Response (Error)
```json
{
  "status": "error",
  "message": "Data absensi tidak ditemukan",
  "errors": null
}
```

## Dokumentasi API

### Swagger UI
Akses: `http://localhost:8000/api/documentation`

### GraphQL Playground
Akses: `http://localhost:8000/graphiql`

#### Contoh Query GraphQL
```graphql
{
  absensis {
    id
    karyawan_id
    nama_karyawan
    bulan
    tahun
    total_hadir
    total_sakit
    total_izin
    total_alpha
    total_hari_kerja
  }
}
```

```graphql
{
  absensi(id: 1) {
    id
    nama_karyawan
    bulan
    tahun
    total_hadir
  }
}
```

## Struktur Project

```
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/AbsensiController.php
│   │   └── Middleware/ApiKeyMiddleware.php
│   └── Models/Absensi.php
├── database/
│   ├── migrations/
│   ├── seeders/AbsensiSeeder.php
│   └── factories/AbsensiFactory.php
├── graphql/schema.graphql
├── routes/api.php
├── Dockerfile
├── docker-compose.yml
└── README.md
```
