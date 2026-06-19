# Prompt Engineering Log — Tugas 3 IAE
Nama: I Gede Satriya Pradnya Wiguna 
NIM : 102022400173
Akun: warga34@ktp.iae.id | KEY-MHS-238  
Service: Payroll Service (Human Resource)  
Stack: Laravel 11, PHP, MySQL, Docker  

---

## Konteks Awal

Sebelum mulai prompting, saya melakukan analisis mandiri terhadap rubrik Tugas 3:

- **Modul 1 (SSO)**: Perlu menangkap JWT dari Cloud Dosen dan memetakan ke role lokal
- **Modul 2 (SOAP)**: Transformasi JSON → XML Envelope kaku, simpan ReceiptNumber
- **Modul 3 (RabbitMQ)**: Publish event JSON ke broker tanpa error
- **Modul 4**: Dokumentasi prompt engineering ini sendiri

Saya juga mengidentifikasi bahwa transaksi `PayrollProcessed` adalah **transaksi kritis keuangan** yang tepat untuk diaudit via SOAP, karena melibatkan perubahan state finansial (net salary, deduction, bonus) yang tidak boleh hilang jejaknya.

---

## Sesi Prompting

---

### Prompt 1 — Review Arsitektur & Identifikasi Bug

**Konteks analisis saya sebelum prompt**:  
Saya sudah menulis `PayrollController.php`, `SsoService.php`, `SoapAuditService.php`, dan `RabbitMqService.php`. Sebelum testing, saya ingin validasi apakah ada bug struktural yang tidak kasat mata, khususnya di:
1. Urutan orkestrasi SSO → SOAP → RabbitMQ
2. Kemungkinan nilai null yang tidak tertangani
3. Konsistensi format TeamID dengan spesifikasi dosen

**Prompt**:
> Saya sedang mengerjakan Tugas 3 IAE yang mengharuskan service saya melakukan orkestrasi 3 lapis secara berurutan: Login SSO Dosen → Kirim SOAP Audit → Broadcast Event ke RabbitMQ. Saya sudah membuat implementasinya di Laravel. Tolong review kode berikut secara menyeluruh dan identifikasi bug atau inkonsistensi, terutama terkait: (1) kemungkinan nilai null yang tidak di-handle, (2) format TeamID di SOAP envelope, (3) apakah RabbitMQ URL bisa return null dari config. Berikut kodenya: [PayrollController.php, SsoService.php, SoapAuditService.php, RabbitMqService.php, .env]

**Hasil & Temuan AI**:
- **Bug 1**: `RabbitMqService` menggunakan `config('services.rabbitmq.url')` tapi key tersebut tidak ada di `config/services.php`, sehingga URL akan selalu `null` dan request tidak pernah terkirim
- **Bug 2**: `$this->teamId = 'TEAM-' . env('SSO_API_KEY')` menghasilkan `TEAM-KEY-MHS-238` (salah), seharusnya `TEAM-238`
- **Info**: Alur orkestrasi sudah benar — RabbitMQ dipublish setelah SOAP, keduanya di dalam guard `if ($token)`

**Tindakan**:
```php
// Fix RabbitMqService.php
$this->url = env('RABBITMQ_URL', 'https://iae-sso.virtualfri.id/api/v1/messages/publish');

// Fix SoapAuditService.php
$this->teamId = 'TEAM-238';
```

---

### Prompt 2 — Login via Password (User Token vs M2M Token)

**Konteks analisis saya**:  
Spesifikasi dosen menyediakan dua metode login: M2M via `api_key` dan User via `email+password`. Saya perlu memahami kapan masing-masing digunakan dan bagaimana implementasinya dengan caching agar tidak login ulang setiap request.

**Prompt**:
> Di SsoService saya sudah ada `loginWithApiKey()` dan `loginWithCredentials()`. Saya ingin refactor ini menjadi `getM2MToken()` dan `getUserToken()` yang keduanya menggunakan Laravel Cache dengan TTL 50 menit agar tidak hit SSO endpoint setiap request. Tolong buatkan implementasinya beserta penjelasan kapan masing-masing method sebaiknya digunakan dalam konteks Tugas 3 ini.

**Hasil**:
```php
public function getM2MToken(): string
{
    return Cache::remember('sso_m2m_token', 50 * 60, function () {
        $response = Http::post("{$this->baseUrl}/api/v1/auth/token", [
            'api_key' => $this->apiKey,
        ]);
        if ($response->failed()) {
            throw new \RuntimeException('SSO M2M login gagal: ' . $response->body());
        }
        return $response->json('token') ?? $response->json('access_token');
    });
}
```

**Insight dari AI**: Untuk Tugas 3, `getM2MToken()` lebih tepat karena orkestrasi SSO → SOAP → RabbitMQ adalah proses sistem (bukan atas nama user). `getUserToken()` relevan jika ada fitur login interaktif di frontend.

---

### Prompt 3 — Fix Timestamp RabbitMQ ke WIB (UTC+7)

**Konteks analisis saya**:  
Timestamp default Laravel menggunakan UTC. Untuk konsistensi log audit di sistem dosen yang kemungkinan dioperasikan di zona WIB, saya perlu memastikan timestamp yang dipublish ke RabbitMQ sudah dalam format WIB dengan offset yang eksplisit.

**Prompt**:
> Di RabbitMqService saya ada `'timestamp' => now()->toIso8601String()`. Saya ingin mengubahnya ke WIB (UTC+7) dengan format ISO 8601 yang tetap menyertakan offset timezone eksplisit. Baris mana yang perlu diubah dan apa hasilnya?

**Hasil**:
```php
// Sebelum
'timestamp' => now()->toIso8601String(),
// Output: 2026-06-18T10:00:00+00:00

// Sesudah
'timestamp' => now('Asia/Jakarta')->toIso8601String(),
// Output: 2026-06-18T17:00:00+07:00
```

---

### Prompt 4 — Panduan Testing Bertahap di Postman

**Konteks analisis saya**:  
Sebelum menjalankan endpoint utama `/api/v1/payrolls/process` yang memanggil ketiga sistem eksternal sekaligus, saya perlu melakukan testing terisolasi per modul untuk mempermudah debugging jika ada yang gagal. Urutan testing: SSO → SOAP → RabbitMQ → endpoint utama.

**Prompt**:
> Tolong buatkan panduan testing bertahap di Postman untuk memvalidasi ketiga modul secara terpisah sebelum menguji endpoint utama. Sertakan: method, URL, headers, dan contoh body untuk masing-masing: (1) SSO M2M login, (2) SOAP Audit manual, (3) RabbitMQ publish manual. Sertakan juga response yang diharapkan untuk masing-masing step.

**Hasil testing aktual**:

| Step | Endpoint | Status | Hasil |
|------|----------|--------|-------|
| SSO M2M | `POST /api/v1/auth/token` | ✅ 200 | JWT token diterima |
| SOAP Audit | `POST /soap/v1/audit` | ✅ 200 | `IAE-LOG-2026-XXXXXXXX` |
| RabbitMQ | `POST /api/v1/messages/publish` | ✅ 200 | Pesan muncul di papan broker |

**Catatan debugging selama testing**:

**Error 1 — SOAP 403 Forbidden**
```xml
<faultstring>Forbidden: M2M Bearer token required.</faultstring>
```
**Root cause**: Saya menggunakan token dari login `email+password`, bukan `api_key`. SOAP endpoint khusus membutuhkan M2M token.  
**Fix**: Ulangi login dengan body `{ "api_key": "KEY-MHS-238" }`, gunakan token tersebut.

**Error 2 — RabbitMQ 400**
```json
{ "status": "error", "message": "message (object or string) is required." }
```
**Root cause**: Body yang saya kirim langsung berisi field `event`, `timestamp`, `data` — tapi endpoint mengharuskan payload dibungkus dalam field `message`.  
**Fix**:
```json
{
  "routing_key": "payroll.processed",
  "message": {
    "event": "payroll.processed",
    "timestamp": "2026-06-18T17:00:00+07:00",
    "data": { ... }
  }
}
```

**Error 3 — Routing key tidak tampil di papan broker**  
**Symptom**: Kotak di papan RabbitMQ menampilkan `-` sebagai nama aktivitas, sedangkan mahasiswa lain tampil nama routing key-nya.  
**Root cause**: Field `routing_key` tidak disertakan di body request.  
**Fix**: Tambahkan `"routing_key": "payroll.processed"` di level atas body JSON, update juga di `RabbitMqService.php`.

---

### Prompt 5 — Update RabbitMqService dengan Struktur Payload yang Benar

**Prompt**:
> Berdasarkan hasil testing, struktur payload RabbitMQ yang benar harus membungkus data dalam field `message` dan menyertakan `routing_key` di level atas. Tolong update method `publishPayrollProcessed()` di RabbitMqService.php dengan struktur yang sudah tervalidasi ini.

**Hasil — kode final RabbitMqService**:
```php
$message = [
    'routing_key' => 'payroll.processed',
    'message'     => [
        'event'     => 'payroll.processed',
        'timestamp' => now('Asia/Jakarta')->toIso8601String(),
        'data'      => [
            'employee_id'    => $payrollData['employee_id'],
            'employee_name'  => $payrollData['employee_name'],
            'period_month'   => $payrollData['period_month'],
            'period_year'    => $payrollData['period_year'],
            'net_salary'     => $payrollData['net_salary'],
            'receipt_number' => $payrollData['receipt_number'] ?? null,
            'status'         => 'processed',
        ],
    ],
];
```

---

## Ringkasan Capaian Teknis

| Modul | Status | Bukti |
|-------|--------|-------|
| Modul 1 — Federated SSO | ✅ Selesai | JWT diterima dari `POST /api/v1/auth/token` dengan `api_key` |
| Modul 2 — SOAP XML Client | ✅ Selesai | ReceiptNumber `IAE-LOG-2026-*` tersimpan di DB |
| Modul 3 — AMQP Publisher | ✅ Selesai | Pesan muncul di papan broker `iae.central.exchange` |
| Modul 4 — Log ini | ✅ Selesai | File ini |

---

## Refleksi Proses Prompting

Pendekatan yang efektif dalam sesi ini:

1. **Isolasi per modul** — testing SSO, SOAP, RabbitMQ secara terpisah sebelum menggabungkan, sehingga root cause error mudah diidentifikasi
2. **Prompt berbasis analisis** — setiap prompt diawali dengan analisis konteks terlebih dahulu, bukan langsung tanya tanpa arah
3. **Iteratif berdasarkan error nyata** — prompt tidak hanya teori, tapi diperbarui berdasarkan error aktual dari Postman
4. **Spesifik pada kode** — menyertakan kode aktual di prompt menghasilkan jawaban yang langsung applicable, bukan generik

