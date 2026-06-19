Analisis Implementasi Tugas 3
1. Implementasi Single Sign-On (SSO)
Pada tugas ini, layanan Data Karyawan telah berhasil diintegrasikan dengan layanan Single Sign-On (SSO) yang disediakan dosen. SSO digunakan sebagai mekanisme autentikasi terpusat sehingga pengguna tidak perlu melakukan autentikasi secara terpisah pada setiap layanan. Ketika pengguna melakukan login, sistem mengirimkan kredensial ke server SSO dan menerima token JWT sebagai bukti autentikasi. Token tersebut kemudian digunakan untuk mengakses endpoint yang dilindungi pada layanan Data Karyawan.
Implementasi ini meningkatkan keamanan sistem karena proses autentikasi tidak lagi dilakukan secara lokal pada masing-masing layanan, melainkan dikelola secara terpusat.

2. Implementasi SOAP Audit Service
Setiap aktivitas penting yang dilakukan pada layanan dicatat melalui SOAP Audit Service. Ketika suatu proses berhasil dijalankan, sistem akan mengirimkan informasi audit seperti nama layanan, aktivitas yang dilakukan, waktu eksekusi, dan status proses ke layanan SOAP.
Hasil pengujian menunjukkan bahwa sistem berhasil menerima Receipt Number dari SOAP Service sebagai bukti bahwa log audit telah tersimpan. Implementasi ini membantu meningkatkan aspek monitoring dan pelacakan aktivitas sistem.

3. Implementasi RabbitMQ Messaging
RabbitMQ digunakan sebagai message broker untuk mendukung komunikasi asynchronous. Setelah suatu proses berhasil dilakukan, sistem mengirimkan pesan ke RabbitMQ Exchange sehingga pesan dapat diteruskan kepada consumer yang membutuhkan informasi tersebut.
Implementasi RabbitMQ memungkinkan proses integrasi antarlayanan menjadi lebih fleksibel karena pengirim dan penerima pesan tidak saling bergantung secara langsung (loosely coupled).

4. Integrasi Keseluruhan Sistem
Ketiga komponen integrasi berhasil dihubungkan ke dalam layanan Data Karyawan. Alur proses dimulai dari autentikasi pengguna melalui SSO, dilanjutkan dengan pemrosesan data pada layanan utama, pencatatan aktivitas ke SOAP Audit Service, dan pengiriman notifikasi melalui RabbitMQ.
Integrasi ini menunjukkan penerapan konsep Enterprise Application Integration (EAI) yang memanfaatkan berbagai pendekatan komunikasi seperti REST API, SOAP Service, dan Message Broker dalam satu arsitektur layanan.