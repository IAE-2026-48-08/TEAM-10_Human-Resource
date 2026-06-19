# Data Karyawan Service

## Identitas

* Nama Service: Data Karyawan Service
* NIM: 102022400090
* Framework: Laravel 13
* Database: MySQL
* Dokumentasi API: Swagger/OpenAPI

## Deskripsi

Data Karyawan Service merupakan REST API yang digunakan untuk mengelola data karyawan. Service ini menyediakan fitur untuk melihat seluruh data karyawan, melihat detail karyawan berdasarkan ID, dan menambahkan data karyawan baru.

## Teknologi yang Digunakan

* PHP 8.3
* Laravel 13
* MySQL
* L5-Swagger
* GitHub

## Cara Menjalankan Project

1. Clone repository

```bash
git clone https://github.com/IAE-2026-48-08/102022400090_Data-Karyawan.git
```

2. Masuk ke folder project

```bash
cd 102022400090_Data-Karyawan
```

3. Install dependency

```bash
composer install
```

4. Konfigurasi file .env

```env
DB_DATABASE=data_karyawan
DB_USERNAME=root
DB_PASSWORD=

API_KEY=102022400090
```

5. Jalankan migrasi database

```bash
php artisan migrate
```

6. Jalankan server

```bash
php artisan serve
```

## Authentication

Seluruh endpoint API dilindungi menggunakan API Key.

Header yang digunakan:

```http
X-IAE-KEY: 102022400090
```

## API Endpoints

### Get All Employees

```http
GET /api/v1/employees
```

### Get Employee By ID

```http
GET /api/v1/employees/{id}
```

### Create Employee

```http
POST /api/v1/employees
```

## Swagger Documentation

Akses dokumentasi API melalui:

```text
http://127.0.0.1:8000/api/documentation
```

## Format Response

### Success Response

```json
{
  "status": "success",
  "message": "Data retrieved successfully",
  "data": {},
  "meta": {
    "service_name": "Data-Karyawan-Service",
    "api_version": "v1"
  }
}
```

### Error Response

```json
{
  "status": "error",
  "message": "Unauthorized - Invalid or missing API Key",
  "errors": null
}
```
