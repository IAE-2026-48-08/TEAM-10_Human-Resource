Analisis Tugas 3 — Payroll Service

Nama: I Gede Satriya Pradnya Wiguna
NIM: 102022400173
Akun: warga34@ktp.iae.id
Service: Payroll Service (Human Resource)

1. Justifikasi Transaksi Kritis

- Transaksi PayrollProcessed dipilih sebagai transaksi kritis** karena transaksi ini melibatkan perubahan data keuangan karyawan secara permanen, yaitu perhitungan gaji bersih (net salary) yang mencakup gaji pokok, potongan absensi, dan bonus. Transaksi ini bersifat state-changing karena sekali diproses, data gaji karyawan akan tersimpan di database dan tidak bisa diubah sembarangan tanpa jejak audit yang jelas.

- Transaksi ini melibatkan data finansial yang sensitif seperti base_salary, deduction, bonus, dan net_salary yang secara langsung berdampak pada hak karyawan. Kesalahan dalam transaksi ini dapat menyebabkan kerugian finansial baik bagi karyawan maupun perusahaan, sehingga setiap pemrosesan gaji wajib diaudit dan dicatat jejaknya secara permanen.

- Alasan penggunaan SOAP Audit pada transaksi ini adalah karena sistem audit legacy (SOAP/XML) dirancang khusus untuk mencatat transaksi-transaksi kritis yang tidak boleh hilang. Dengan mengirimkan data payroll ke endpoint /soap/v1/audit, setiap pemrosesan gaji akan mendapatkan ReceiptNumber unik sebagai bukti bahwa transaksi telah tercatat di sistem audit pusat.

- Alasan penggunaan RabbitMQ pada transaksi ini adalah untuk menyebarkan notifikasi pemrosesan gaji secara asinkron ke seluruh departemen yang berkepentingan, seperti departemen keuangan dan HRD. Dengan pendekatan ini, setiap departemen dapat merespons event payroll.processed secara independen tanpa harus menunggu proses payroll selesai secara sinkron.

2. Skema Role Lokal

- Role admin memiliki akses penuh ke seluruh endpoint payroll, termasuk memproses gaji (POST /api/v1/payrolls/process), melihat seluruh data penggajian (GET /api/v1/payrolls), dan melihat detail penggajian per karyawan (GET /api/v1/payrolls/{id}). Role ini diperuntukkan bagi manajer HR atau admin sistem yang bertanggung jawab atas pemrosesan penggajian.

- Role employee hanya memiliki akses terbatas untuk melihat data penggajian miliknya sendiri (GET /api/v1/payrolls/{id}). Role ini tidak dapat memproses gaji atau melihat data penggajian karyawan lain, sehingga privasi data keuangan setiap karyawan tetap terjaga.

- Pemetaan role dilakukan secara lokal setelah JWT token diterima dari SSO Cloud Dosen. Payload JWT yang diterima akan diverifikasi dan di-decode, kemudian sistem akan memetakan identitas user ke tabel role lokal untuk menentukan hak akses yang sesuai di dalam Payroll Service.

3. Sequence Diagram Interaksi dengan Sistem Terpusat

- Alur dimulai dari Client yang mengirimkan request POST /api/v1/payrolls/process ke PayrollController dengan data karyawan, periode, gaji pokok, dan data absensi. PayrollController kemudian memvalidasi input dan menghitung gaji bersih sebelum melanjutkan ke proses orkestrasi sistem terpusat.

- Tahap pertama adalah SSO Login dimana PayrollController memanggil loginWithApiKey() melalui LocalService, yang kemudian mengirimkan request POST /api/v1/auth/token dengan body {api_key: "KEY-MHS-238", nim: "102022400173"} ke Cloud Dosen. Jika berhasil, Cloud Dosen akan mengembalikan JWT token yang di-cache selama 50 menit untuk menghindari login berulang pada setiap request.

- Tahap kedua adalah SOAP Audit dimana setelah token diterima, LocalService mengirimkan XML Envelope ke endpoint /soap/v1/audit dengan Bearer token dan data payroll yang dibungkus dalam CDATA. Cloud Dosen akan memproses XML tersebut dan mengembalikan ReceiptNumber unik yang kemudian disimpan ke database sebagai bukti audit transaksi kritis ini.

- Tahap ketiga adalah RabbitMQ Publish dimana LocalService mengirimkan event payroll.processed dalam format JSON ke endpoint /api/v1/messages/publish dengan routing_key: "payroll.processed". Cloud Dosen akan meneruskan pesan ini ke exchange iae.central.exchange sehingga seluruh service yang berlangganan dapat menerima notifikasi pemrosesan gaji secara asinkron.

- Alur diakhiri dengan response 201 dari PayrollController ke Client yang berisi data payroll yang telah diproses, termasuk receipt_number dari hasil SOAP Audit dan metadata status koneksi SSO dan SOAP sebagai konfirmasi bahwa seluruh orkestrasi 3 lapis telah berjalan dengan sukses.

4. Kesimpulan

- Transaksi PayrollProcessed terbukti memenuhi kriteria transaksi kritis karena bersifat state-changing pada data keuangan, melibatkan perhitungan finansial yang sensitif, dan membutuhkan jejak audit yang permanen. Kombinasi SSO untuk autentikasi, SOAP untuk audit legacy, dan RabbitMQ untuk broadcast event menjadikan transaksi ini terlindungi dan terdokumentasi dengan baik di sistem terpusat.

- Implementasi orkestrasi 3 lapis berhasil divalidasi melalui pengujian di Postman, dimana SSO berhasil mengembalikan JWT token, SOAP berhasil mengembalikan ReceiptNumber IAE-LOG-2026, dan pesan RabbitMQ berhasil muncul di papan broker iae.central.exchange dengan routing key payroll.processed.
