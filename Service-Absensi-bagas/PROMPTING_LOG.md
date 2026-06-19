# Log Prompting AI: Pembangunan Service Absensi (Laravel + Swagger + GraphQL)

## 💬 Sesi 1: Inisialisasi Proyek Laravel & Setup Database SQLite

**User:**
> Halo AI, saya sedang mengerjakan tugas kuliah untuk membuat **Service Absensi Karyawan** berbasis REST API menggunakan Laravel.
> Ketentuan teknis:
> 1. Gunakan Laravel versi terbaru (Laravel 12).
> 2. Database menggunakan **SQLite** agar mudah dijalankan secara lokal tanpa perlu setup server database terpisah.
>
> Tolong bantu saya dengan langkah-langkah instalasi awal proyek, pembuatan file database SQLite, serta konfigurasi file `.env`-nya.

---

**AI:**
Tentu! Mari kita mulai dari langkah awal. Kita akan membuat proyek Laravel baru bernama `Service-Absensi-Bagas` dan mengonfigurasinya menggunakan SQLite.

### Langkah 1: Instalasi Proyek Laravel
Jalankan perintah berikut di terminal Anda untuk mengunduh dan membuat proyek Laravel baru:
```bash
composer create-project laravel/laravel Service-Absensi-Bagas
cd Service-Absensi-Bagas
```

### Langkah 2: Membuat File Database SQLite
Secara default, Laravel sudah mendukung SQLite. Kita perlu membuat file kosong database-nya di direktori `database`:
* **Di Windows PowerShell:**
  ```powershell
  New-Item -Path "database/database.sqlite" -ItemType File
  ```

### Langkah 3: Konfigurasi File `.env`
Buka file `.env` di editor kode Anda, cari bagian konfigurasi database (`DB_CONNECTION`), dan ubah menjadi seperti di bawah ini (pastikan menghapus baris konfigurasi DB lainnya seperti `DB_HOST`, `DB_PORT`, dll.):
```env
DB_CONNECTION=sqlite
# DB_DATABASE secara default akan diarahkan otomatis oleh Laravel 12 ke database/database.sqlite jika dikosongkan,
# namun untuk kejelasan, kita bisa menuliskan path absolutnya.
```

Setelah konfigurasi selesai, jalankan perintah ini untuk memastikan koneksi berhasil:
```bash
php artisan db:show
```

---

## 💬 Sesi 2: Pembuatan Migration, Model, dan Database Seeder

**User:**
> Oke, langkah pertama sukses. Sekarang tolong buatkan skema tabel database untuk absensi. 
> Field tabel `absensis` yang dibutuhkan:
> - `id` (Primary Key, Auto Increment)
> - `nama_karyawan` (string)
> - `bulan` (string, misal: "Januari", "Februari")
> - `tahun` (integer, misal: 2026)
> - `total_hadir` (integer)
> - `total_sakit` (integer)
> - `total_izin` (integer)
> - `total_alfa` (integer)
> - `timestamps` (created_at & updated_at)
>
> Buatkan file migrasi database (migration), model `Absensi`, dan seeder untuk menghasilkan data dummy sebanyak 50 baris agar saya bisa melakukan pengujian.

---

**AI:**
Berikut adalah implementasi Migration, Model, dan Seeder untuk tabel absensi.

### 1. Membuat Migration & Model
Jalankan perintah Artisan berikut untuk membuat Model beserta file Migration secara bersamaan:
```bash
php artisan make:model Absensi -m
```

Buka file migration yang baru dibuat di `database/migrations/xxxx_xx_xx_xxxxxx_create_absensis_table.php`, lalu ubah fungsi `up()` menjadi:
```php
public function up(): void
{
    Schema::create('absensis', function (Blueprint $table) {
        $table->id();
        $table->string('nama_karyawan');
        $table->string('bulan');
        $table->integer('tahun');
        $table->integer('total_hadir')->default(0);
        $table->integer('total_sakit')->default(0);
        $table->integer('total_izin')->default(0);
        $table->integer('total_alfa')->default(0);
        $table->timestamps();
    });
}
```

Buka file model `app/Models/Absensi.php` dan tambahkan properti `$fillable`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_karyawan',
        'bulan',
        'tahun',
        'total_hadir',
        'total_sakit',
        'total_izin',
        'total_alfa'
    ];
}
```

### 2. Membuat Database Seeder
Buat seeder baru untuk absensi dengan perintah:
```bash
php artisan make:seeder AbsensiSeeder
```

Buka file `database/seeders/AbsensiSeeder.php` dan gunakan class Faker untuk meng-generate 50 data dummy:
```php
<?php

namespace Database\Seeders;

use App\Models\Absensi;
use Illuminate\Database\Seeder;

class AbsensiSeeder extends Seeder
{
    public function run(): void
    {
        $karyawan = ['Budi Santoso', 'Siti Aminah', 'Rian Hidayat', 'Dewi Lestari', 'Eko Prasetyo', 'Aditya Pratama', 'Rina Wijaya'];
        $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        for ($i = 0; $i < 50; $i++) {
            $hadir = rand(18, 22);
            $sakit = rand(0, 2);
            $izin = rand(0, 2);
            $alfa = rand(0, 1);

            Absensi::create([
                'nama_karyawan' => $karyawan[array_rand($karyawan)],
                'bulan' => $bulan[array_rand($bulan)],
                'tahun' => rand(2025, 2026),
                'total_hadir' => $hadir,
                'total_sakit' => $sakit,
                'total_izin' => $izin,
                'total_alfa' => $alfa,
            ]);
        }
    }
}
```

Daftarkan seeder tersebut di `database/seeders/DatabaseSeeder.php`:
```php
public function run(): void
{
    $this->call([
        AbsensiSeeder::class,
    ]);
}
```

Terakhir, jalankan proses migrasi dan pengisian database:
```bash
php artisan migrate --seed
```

---

## 💬 Sesi 3: Middleware Keamanan API Key (`X-IAE-KEY`) dengan NIM

**User:**
> REST API ini harus aman dan tidak boleh diakses oleh publik tanpa kunci keamanan.
> Saya ingin menerapkan pengamanan **API Key** menggunakan header kustom bernama `X-IAE-KEY`.
> Nilai key yang valid adalah NIM saya: **`102022400319`**.
> Jika request tidak membawa header tersebut, atau nilainya salah, API harus merespon dengan status code **401 Unauthorized** dan format JSON standar integrasi:
> ```json
> {
>   "status": "error",
>   "message": "API Key tidak valid atau tidak disertakan.",
>   "data": null,
>   "meta": null
> }
> ```
> Tolong buatkan middleware Laravel untuk menangani skenario ini.

---

**AI:**
Kita bisa membuat middleware khusus untuk memvalidasi header tersebut. Berikut adalah langkah-langkahnya:

### 1. Membuat Middleware
Jalankan perintah ini untuk membuat middleware baru bernama `ApiKeyMiddleware`:
```bash
php artisan make:middleware ApiKeyMiddleware
```

### 2. Mengisi Logika Middleware
Buka file `app/Http/Middleware/ApiKeyMiddleware.php` dan modifikasi fungsinya untuk membaca header `X-IAE-KEY`:
```php
<?php

namespace App\Http/Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedKey = '102022400319'; // NIM User
        $providedKey = $request->header('X-IAE-KEY');

        if (!$providedKey || $providedKey !== $expectedKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'API Key tidak valid atau tidak disertakan.',
                'data' => null,
                'meta' => null
            ], 401);
        }

        return $next($request);
    }
}
```

### 3. Mendaftarkan Middleware di Laravel 12
Di Laravel 12, pendaftaran middleware dilakukan di file `bootstrap/app.php`. Daftarkan alias middleware Anda di sana agar dapat digunakan di rute API:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'api.key' => \App\Http\Middleware\ApiKeyMiddleware::class,
    ]);
})
```

Sekarang middleware `api.key` sudah siap digunakan pada rute-rute API kita.

---

## 💬 Sesi 4: Pembuatan REST Controller, Validasi Input, Rute, & Dokumentasi Swagger (L5-Swagger)

**User:**
> Sekarang buatlah REST Controller bernama `AbsensiController`.
> Endpoint yang wajib dibuat:
> 1. `GET /api/v1/absensi` (Mengambil semua data absensi, dukung filter query parameter `bulan` dan `tahun`).
> 2. `GET /api/v1/absensi/{id}` (Detail data absensi).
> 3. `POST /api/v1/absensi` (Menambahkan rekap baru. Beri validasi input dan pastikan tidak ada duplikasi data absensi untuk kombinasi `nama_karyawan`, `bulan`, dan `tahun` yang sama).
>
> Semua respon JSON wajib menggunakan format wrapper standar: `{ status, message, data, meta }`.
> Pasang juga anotasi Swagger (OpenAPI) di file controllernya agar saya bisa meng-generate dokumentasi Swagger secara otomatis menggunakan package `L5-Swagger`.

---

**AI:**
Baik, pertama kita akan menginstal `L5-Swagger` dan kemudian menyusun Controller lengkap dengan anotasi Swagger PHP 8 Attributes.

### 1. Instalasi L5-Swagger
Jalankan perintah composer berikut untuk memasang L5-Swagger di proyek:
```bash
composer require "darkaonline/l5-swagger"
```
Publish file konfigurasi l5-swagger:
```bash
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

### 2. Membuat AbsensiController dengan Anotasi Swagger
Buat controller baru dengan perintah:
```bash
php artisan make:controller Api/V1/AbsensiController
```

Buka file `app/Http/Controllers/Api/V1/AbsensiController.php` dan tulis kode berikut. Kami menggunakan PHP Attributes (OpenAPI 3.0) yang didukung penuh oleh PHP 8.2+:
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Service Absensi API",
    description: "API Dokumentasi untuk Service Absensi Karyawan - Tugas Kuliah Bagas"
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Lokal Server"
)]
#[OA\SecurityScheme(
    securityScheme: "ApiKeyAuth",
    type: "apiKey",
    name: "X-IAE-KEY",
    in: "header",
    description: "Masukkan NIM Anda (102022400319) sebagai API Key"
)]
class AbsensiController extends Controller
{
    #[OA\Get(
        path: "/api/v1/absensi",
        summary: "Mendapatkan semua rekap absensi",
        security: [["ApiKeyAuth" => []]],
        tags: ["Absensi"],
        parameters: [
            new OA\Parameter(name: "bulan", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "tahun", in: "query", required: false, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function index(Request $request)
    {
        $query = Absensi::query();

        if ($request->has('bulan')) {
            $query->where('bulan', $request->bulan);
        }

        if ($request->has('tahun')) {
            $query->where('tahun', $request->tahun);
        }

        $data = $query->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Data absensi berhasil diambil.',
            'data' => $data,
            'meta' => [
                'total' => $data->count()
            ]
        ], 200);
    }

    #[OA\Get(
        path: "/api/v1/absensi/{id}",
        summary: "Mendapatkan detail absensi berdasarkan ID",
        security: [["ApiKeyAuth" => []]],
        tags: ["Absensi"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 404, description: "Not Found"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function show($id)
    {
        $absensi = Absensi::find($id);

        if (!$absensi) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data absensi tidak ditemukan.',
                'data' => null,
                'meta' => null
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Detail data absensi berhasil diambil.',
            'data' => $absensi,
            'meta' => null
        ], 200);
    }

    #[OA\Post(
        path: "/api/v1/absensi",
        summary: "Menambahkan/Generate rekap absensi baru",
        security: [["ApiKeyAuth" => []]],
        tags: ["Absensi"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["nama_karyawan", "bulan", "tahun"],
                properties: [
                    new OA\Property(property: "nama_karyawan", type: "string"),
                    new OA\Property(property: "bulan", type: "string"),
                    new OA\Property(property: "tahun", type: "integer"),
                    new OA\Property(property: "total_hadir", type: "integer"),
                    new OA\Property(property: "total_sakit", type: "integer"),
                    new OA\Property(property: "total_izin", type: "integer"),
                    new OA\Property(property: "total_alfa", type: "integer")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Created"),
            new OA\Response(response: 400, description: "Bad Request"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_karyawan' => 'required|string|max:255',
            'bulan' => 'required|string',
            'tahun' => 'required|integer',
            'total_hadir' => 'nullable|integer',
            'total_sakit' => 'nullable|integer',
            'total_izin' => 'nullable|integer',
            'total_alfa' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'data' => $validator->errors(),
                'meta' => null
            ], 400);
        }

        // Cek duplikasi kombinasi karyawan, bulan, dan tahun
        $exists = Absensi::where('nama_karyawan', $request->nama_karyawan)
            ->where('bulan', $request->bulan)
            ->where('tahun', $request->tahun)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rekap absensi untuk karyawan ini pada bulan dan tahun tersebut sudah ada.',
                'data' => null,
                'meta' => null
            ], 400);
        }

        $absensi = Absensi::create([
            'nama_karyawan' => $request->nama_karyawan,
            'bulan' => $request->bulan,
            'tahun' => $request->tahun,
            'total_hadir' => $request->total_hadir ?? 0,
            'total_sakit' => $request->total_sakit ?? 0,
            'total_izin' => $request->total_izin ?? 0,
            'total_alfa' => $request->total_alfa ?? 0,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Rekap absensi berhasil ditambahkan.',
            'data' => $absensi,
            'meta' => null
        ], 201);
    }
}
```

### 3. Mendaftarkan Rute API dengan Middleware Keamanan
Buka file rute API `routes/api.php` dan tambahkan kode rute yang dibungkus dengan middleware `api.key` yang telah kita buat:
```php
<?php

use App\Http\Controllers\Api\V1\AbsensiController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.key')->prefix('v1')->group(function () {
    Route::get('/absensi', [AbsensiController::class, 'index']);
    Route::get('/absensi/{id}', [AbsensiController::class, 'show']);
    Route::post('/absensi', [AbsensiController::class, 'store']);
});
```

### 4. Meng-generate Dokumentasi Swagger
Jalankan perintah ini untuk menerjemahkan anotasi di controller menjadi dokumentasi Swagger UI:
```bash
php artisan l5-swagger:generate
```

Kini, jika Anda membuka browser ke alamat `http://localhost:8000/api/documentation`, Anda akan melihat tampilan Swagger UI interaktif!

---

## 💬 Sesi 5: Integrasi GraphQL Menggunakan Lighthouse PHP

**User:**
> Persyaratan tambahan dari dosen adalah mendukung **GraphQL API**.
> Tolong bantu instalasikan library GraphQL di Laravel (saya merekomendasikan **Lighthouse PHP** karena sangat laravel-friendly).
> Buatkan juga skema GraphQL-nya (`schema.graphql`) agar bisa men-query seluruh data absensi dan detail data absensi berdasarkan ID, serta aktifkan Playground (GraphiQL) untuk mempermudah testing.

---

**AI:**
Lighthouse PHP adalah pilihan terbaik untuk mengimplementasikan GraphQL secara mudah di Laravel. Berikut langkah konfigurasinya:

### 1. Instalasi Lighthouse & GraphiQL Playground
Jalankan perintah composer untuk memasang package Lighthouse dan antarmuka GraphiQL:
```bash
composer require nuwave/lighthouse
composer require mll-lab/laravel-graphiql
```

### 2. Publish Konfigurasi & Skema Awal
Publish file konfigurasi Lighthouse dan file skema default:
```bash
php artisan vendor:publish --provider="Nuwave\Lighthouse\LighthouseServiceProvider" --tag="schema"
php artisan vendor:publish --provider="Nuwave\Lighthouse\LighthouseServiceProvider" --tag="config"
```

### 3. Menyusun File Skema GraphQL (`graphql/schema.graphql`)
Buka file `graphql/schema.graphql` yang baru digenerate, hapus isinya, dan ubah dengan skema absensi kita:
```graphql
"A datetime string with format `Y-m-d H:i:s`, e.g. `2018-05-23 13:43:32`."
scalar DateTime @scalar(class: "Nuwave\\Lighthouse\\Schema\\Types\\Scalars\\DateTime")

type Query {
    "Mendapatkan seluruh rekap absensi"
    absensis: [Absensi!]! @all

    "Mendapatkan rekap absensi berdasarkan ID"
    absensi(id: ID! @eq): Absensi @find
}

type Absensi {
    id: ID!
    nama_karyawan: String!
    bulan: String!
    tahun: Int!
    total_hadir: Int!
    total_sakit: Int!
    total_izin: Int!
    total_alfa: Int!
    created_at: DateTime!
    updated_at: DateTime!
}
```

Sekarang jalankan server Laravel Anda, lalu buka **`http://localhost:8000/graphiql`** di browser Anda untuk mulai menguji query GraphQL!

---

## 💬 Sesi 6: Dockerisasi Aplikasi (Dockerfile & Docker Compose)

**User:**
> Langkah terakhir, buatkan file konfigurasi **Docker** agar service ini dapat dideploy dengan mudah ke dalam kontainer.
> Saya perlu file `Dockerfile` dan `docker-compose.yml` yang terkonfigurasi dengan PHP 8.2/8.3, Composer, SQLite, dan mem-expose port `8000`.

---

**AI:**
Berikut adalah konfigurasi Docker untuk menampung service absensi Anda.

### 1. File `Dockerfile`
Buat file bernama `Dockerfile` di direktori utama (root) proyek Anda, lalu masukkan kode berikut:
```dockerfile
FROM php:8.2-fpm

# Install dependensi sistem dan ekstensi PHP yang dibutuhkan Laravel & SQLite
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libzip-dev \
    libsqlite3-dev \
    sqlite3

# Bersihkan cache sistem
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install ekstensi PHP
RUN docker-php-ext-install pdo pdo_sqlite zip exif pcntl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Atur direktori kerja kontainer
WORKDIR /var/www

# Salin source code proyek ke kontainer
COPY . /var/www

# Install dependensi PHP proyek via composer
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Atur permission agar storage dapat ditulis oleh web server
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Port yang diexpose kontainer
EXPOSE 8000

# Jalankan server bawaan Laravel di port 8000 saat kontainer dinyalakan
CMD php artisan serve --host=0.0.0.0 --port=8000
```

### 2. File `docker-compose.yml`
Buat file `docker-compose.yml` di folder root proyek Anda untuk mengatur orkestrasinya:
```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    image: service-absensi-bagas
    container_name: service_absensi_app
    restart: unless-stopped
    ports:
      - "8000:8000"
    volumes:
      - .:/var/www
      # Volume terpisah untuk database sqlite agar data persisten di komputer host
      - ./database:/var/www/database
    networks:
      - absensi-network

networks:
  absensi-network:
    driver: bridge
```

Aplikasi Anda akan dapat diakses dari browser di alamat `http://localhost:8000`.

---

## 💬 Sesi 7: Integrasi Tugas 3 (Federated SSO, SOAP Client, dan AMQP Publisher)

**User:**
> Halo AI, sekarang saya perlu mengintegrasikan beberapa fitur lanjutan untuk **Tugas 3** di Service Absensi Karyawan:
> 1. **Federated SSO**: Validasi JWT Token (`Authorization: Bearer <JWT>`) dari `https://iae-sso.virtualfri.id/api/v1/auth/jwks` menggunakan library `firebase/php-jwt`. Lakukan registrasi/pemetaan otomatis data user dan local roles ke tabel database (`roles` & `role_user`).
> 2. **Role-Based Access Control (RBAC)**: Lindungi transaksi kritis pembuatan data absensi (`POST /api/v1/absensi`) agar hanya bisa diakses oleh role `admin` (M2M Client), sedangkan role `warga` (User SSO) hanya memiliki hak akses membaca saja (GET).
> 3. **SOAP XML Client**: Kirim log audit transaksi kritis ke `POST https://iae-sso.virtualfri.id/soap/v1/audit` menggunakan format strict SOAP XML, ekstrak nilai `ReceiptNumber` dari respon, lalu simpan ke database absensi lokal.
> 4. **AMQP Event Publisher**: Sebarkan notification event format JSON ke broker RabbitMQ dosen via gateway endpoint `POST https://iae-sso.virtualfri.id/api/v1/messages/publish`.
> 
> Tolong berikan langkah-langkah implementasi, file migrasi database, model tambahan, pembaruan middleware keamanan, dan controller integrasi.

---

**AI:**
Tentu! Mari kita kerjakan keempat modul integrasi tersebut secara sistematis.

### Langkah 1: Instalasi Library Firebase JWT
Jalankan composer di terminal proyek Anda untuk memasang dependency JWT:
```bash
composer require firebase/php-jwt
```

### Langkah 2: Pembuatan Skema Migrasi Database Peran & Kolom Baru
Kita akan membuat tabel `roles`, tabel pivot `role_user`, serta kolom `receipt_number` pada tabel `absensis` menggunakan 3 file migrasi:

1. **`2026_06_13_000001_create_roles_table.php`**:
```php
Schema::create('roles', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->timestamps();
});
```

2. **`2026_06_13_000002_create_role_user_table.php`**:
```php
Schema::create('role_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('role_id')->constrained()->onDelete('cascade');
    $table->timestamps();
    $table->unique(['user_id', 'role_id']);
});
```

3. **`2026_06_13_000003_add_receipt_number_to_absensis_table.php`**:
```php
Schema::table('absensis', function (Blueprint $table) {
    $table->string('receipt_number')->nullable()->after('total_hari_kerja');
});
```

Dan buat `RoleSeeder` untuk menyuntikkan data default `admin`, `karyawan`, dan `warga` lalu jalankan:
```bash
php artisan migrate --seed
```

### Langkah 3: Konfigurasi Hubungan Model User & Peran
Perbarui `app/Models/User.php` dengan relasi `roles` dan metode helper:
```php
public function roles() {
    return $this->belongsToMany(Role::class);
}

public function hasRole(string $roleName): bool {
    return $this->roles()->where('name', $roleName)->exists();
}
```

### Langkah 4: Pembaruan Middleware Keamanan (`ApiKeyMiddleware`)
Ubah logika `app/Http/Middleware/ApiKeyMiddleware.php` agar dapat menangani otentikasi JWT SSO, membuat akun lokal secara dinamis, mengaitkan peran lokal, serta menolak request non-admin pada rute kritis:
```php
// ApiKeyMiddleware handle logic
// 1. Memeriksa header Authorization Bearer JWT
// 2. Decode signature via JWKS terpusat (cached)
// 3. Sync User & Roles (M2M -> admin, User -> warga/karyawan)
// 4. Fallback ke X-IAE-KEY jika tidak ada JWT
// 5. Blokir POST request untuk non-admin role (403 Forbidden)
```

### Langkah 5: Integrasi SOAP & RabbitMQ di `AbsensiController`
Di dalam method `store` milik `AbsensiController.php`:
1. Dapatkan token M2M dari SSO dengan API-KEY: `KEY-MHS-409`.
2. Susun Payload SOAP XML menggunakan XML Envelope kaku, isi data ke dalam CDATA block, lalu POST ke `/soap/v1/audit`.
3. Gunakan regex/parser untuk mengekstrak `ReceiptNumber` dan simpan ke database absensi lokal.
4. Publikasikan pesan JSON event notification via gateway broker dosen `/api/v1/messages/publish`.

