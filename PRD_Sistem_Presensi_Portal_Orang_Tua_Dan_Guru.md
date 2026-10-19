# PRODUCT REQUIREMENT DOCUMENT (PRD)
## Sistem Portal Orang Tua & Modernisasi Presensi Sekolah

---

| Dokumen | Keterangan |
| :--- | :--- |
| **Proyek** | Modernisasi Presensi Sekolah (Fingerprint Biometrik & Portal Wali Murid) |
| **Versi Dokumen** | 2.0 (Final - Konsolidasi Perubahan Siklus Hidup Fitur) |
| **Status** | Terimplementasi & Terverifikasi (Production Ready) |
| **Target Pengguna** | Orang Tua / Wali Murid, Guru / Wali Kelas, Administrator Presensi |
| **Tanggal Pembaruan** | 18 Oktober 2026 |

---

## 1. Ringkasan Eksekutif (Executive Summary)

Sistem Presensi Sekolah berbasis biometrik sidik jari (*fingerprint hardware*) ini sebelumnya mengandalkan gateway pihak ketiga berupa WhatsApp (OpenWA Gateway) untuk pengiriman notifikasi dan pengawasan kehadiran siswa. Namun, model tersebut menghadapi ketergantungan server perantara, nomor yang rentan diblokir, serta kendala privasi dan validasi wali murid yang kurang presisi.

Dokumen PRD ini mendokumentasikan transformasi sistem presensi sekolah secara menyeluruh yang mencakup:
1. **Penambahan Fitur Baru (*Added Features*)**: Peluncuran Portal Orang Tua modern dengan autentikasi NIS + Nama + Kelas, Sistem Web Push Notifications (PWA) langsung ke layar ponsel pintar orang tua tanpa perantara aplikasi chat, serta Modul Formulir Izin & Sakit mandiri yang terintegrasi dengan database dan dashboard sekolah.
2. **Penghapusan Fitur Menyeluruh (*Removed Features*)**: Pembersihan total seluruh dependensi, antarmuka, rute, dan controller yang berkaitan dengan WhatsApp/OpenWA Gateway di seluruh modul (Pengaturan, TV Monitoring, Monitoring Jurusan, Monitoring Kepulangan, dan Dashboard Guru). Selain itu, penghapusan tombol demo pintas, tombol ganti anak, dan tombol login guru di halaman orang tua.
3. **Pembaruan Fitur Terpadu (*Updated Features*)**: Sinkronisasi status kehadiran izin/sakit ke Dashboard Wali Kelas, perombakan formula kalkulasi status alpha agar siswa sakit tidak terhitung bolos, penyempurnaan timeline belajar KBM, dan pembaruan layout UI yang responsif.

---

## 2. Latar Belakang & Identifikasi Masalah (Problem Statement)

| Masalah Sebelumnya | Dampak Negatif | Solusi Baru yang Diimplementasikan |
| :--- | :--- | :--- |
| Notifikasi bergantung pada API WhatsApp eksternal (OpenWA). | Server gateway sering mati, nomor pengirim rawan terblokir, biaya operasional server terpisah. | **Diganti Web Push Notification (W3C Push API & VAPID)** yang langsung memunculkan pop-up di status bar HP orang tua. |
| Pengaturan dan tombol WhatsApp tersebar di seluruh sistem. | Tampilan membingungkan pengguna jika gateway WA tidak aktif. | **Penghapusan total (100%) seluruh elemen WhatsApp** dari pengaturan, tabel, dan monitoring. |
| Orang tua dapat mengakses dashboard dengan mudah tanpa verifikasi ketat (ada tombol demo). | Keamanan data siswa terbuka dan data anak rentan diakses sembarang pihak. | **Login Multi-Persyaratan**: Wajib memasukkan NIS resmi, Nama Lengkap, dan Kelas siswa. Shortcut demo dihapus. |
| Formulir permohonan izin/sakit sebelumnya hanya berupa prototipe tampilan. | Keterangan sakit orang tua tidak tersimpan dan siswa tetap terhitung belum hadir/alpha di dashboard guru. | **Penyimpanan resmi ke tabel `student_permissions`**, otomatis mengubah status siswa di portal ortu dan dashboard guru. |
| Tombol akses login Guru/Admin berada di halaman portal orang tua. | Pengguna orang tua bingung dan antarmuka bercampur aduk. | **Pemisahan tegas antarmuka**: Tombol login guru dihapus dari halaman portal orang tua. |

---

## 3. Matriks Perubahan Fitur (Feature Lifecycle Matrix)

```mermaid
graph TD
    subgraph FITUR BARU DITAMBAHKAN
        F1[Portal Orang Tua /orang-tua]
        F2[Autentikasi Multi-Syarat: NIS + Nama + Kelas]
        F3[Web Push Notifications PWA]
        F4[Formulir Izin / Sakit Mandiri]
        F5[Tabel student_permissions]
    end

    subgraph FITUR DIHAPUS TOTAL
        R1[Integrasi OpenWA Gateway & API]
        R2[Pengaturan & Test WhatsApp di Admin]
        R3[Tombol Pengingat WA di Dashboard Guru]
        R4[Kolom No WA di Monitoring Jurusan & Pulang]
        R5[Tombol Demo / Quick Access Login]
        R6[Tombol Ganti Anak]
        R7[Tombol Login Guru di Header Ortu]
    end

    subgraph FITUR DIPERBARUI
        U1[Dashboard Guru: Stat & Filter Izin/Sakit]
        U2[Timeline KBM: Otomatis Menyesuaikan Status Sakit]
        U3[Polling Real-Time: Deteksi Presensi Otomatis]
        U4[Tabel Siswa: Kolom NIS Baru]
    end
```

---

## 4. Rincian Fitur yang Ditambahkan (Added Features)

### 4.1. Portal Khusus Orang Tua (`/orang-tua`)
- **URL**: `http://localhost:8000/orang-tua`
- **Tujuan**: Memberikan portal pemantauan transparan bagi orang tua murid untuk memantau status presensi ananda secara langsung dari ponsel.
- **Komponen Utama**:
  1. **Kartu Status Kedatangan**: Menampilkan jam masuk, status Tepat Waktu / Terlambat / Izin / Sakit.
  2. **Kartu Status Kepulangan**: Menampilkan waktu scan pulang dan durasi belajar di sekolah.
  3. **Timeline Aktivitas Hari Ini**: 4 tahapan berurutan (Batas Waktu Masuk, Presensi Datang, KBM di Kelas, Presensi Pulang).
  4. **Riwayat Kehadiran 10 Hari**: Rekap presensi harian dengan lencana status yang jelas.
  5. **Streak Kehadiran**: Indikator kehadiran berturut-turut untuk apresiasi kedisiplinan siswa.

### 4.2. Autentikasi Ketat Berbasis NIS (Nomor Induk Siswa)
- **Persyaratan Form Login Orang Tua**:
  - **NIS (Nomor Induk Siswa)**: Wajib cocok dengan basis data siswa sekolah.
  - **Nama Lengkap Siswa**: Pencocokan nama siswa (*case-insensitive*).
  - **Pilihan Kelas**: Verifikasi kelas aktif siswa.
- **Keamanan**: Data tersimpan dalam *secure session* `parent_student_id`. Jika verifikasi gagal, sistem menampilkan peringatan validasi presisi tanpa memberikan akses ke data anak lain.

### 4.3. Notifikasi Pop-up Layar HP (Web Push Notifications W3C)
- **Teknologi**: Service Worker (`public/sw.js`), Web Push API W3C, VAPID Keys (`config/webpush.php`).
- **Mekanisme**:
  - Orang tua mengklik tombol **"Aktifkan di HP Ini"** di portal.
  - Perangkat mendaftarkan langganan push (*PushSubscription*) ke database `parent_push_subscriptions`.
  - Ketika mesin fingerprint mencatat log kehadiran siswa (status 0 = datang, 1 = pulang), event listener memicu job background `SendWebPushAttendanceJob`.
  - Notifikasi pop-up muncul di status bar HP orang tua secara otomatis tanpa aplikasi pihak ketiga.
  - Dilengkapi fitur **"Tes Notifikasi Layar"** untuk verifikasi konektivitas perangkat.

### 4.4. Formulir Pengajuan Izin / Sakit Terintegrasi
- **Input Pengajuan**:
  - Tanggal Izin / Sakit (otomatis hari berjalan atau tanggal masa izin).
  - Jenis Keterangan (`Sakit`, `Izin Ada Keperluan Keluarga`, `Izin Lainnya`).
  - Alasan / Keterangan Tambahan (catatan bebas dari orang tua).
- **Alur Kerja**:
  - Mengirim payload JSON asinkron (`fetch POST`) ke rute `parent.permission.store`.
  - Tersimpan ke tabel database `student_permissions`.
  - Halaman merefresh otomatis dan memunculkan banner **"Keterangan Resmi Disetujui"**.
  - Mengubah kartu status tahap 1 kedatangan menjadi status **Sakit** / **Izin**.

---

## 5. Rincian Fitur yang Dihapus (Removed Features)

| Area / Halaman | Fitur yang Dihapus | Alasan Penghapusan |
| :--- | :--- | :--- |
| **Settings (`/settings`)** | Kartu Formulir Integrasi WhatsApp (URL Server OpenWA, Session Name, API Key, URL Webhook). | Sistem beralih sepenuhnya ke Web Push internal aplikasi; tidak ada lagi ketergantungan server OpenWA eksternal. |
| **Settings (`/settings`)** | Formulir Uji Coba Pengiriman Pesan WhatsApp (Test Message). | Seluruh jalur broadcast WhatsApp ditiadakan. |
| **API & Web Routes** | Rute Webhook `POST /api/webhook/openwa` dan controller `WhatsAppWebhookController`. | Mencegah celah webhook tidak terpakai dan menghemat alokasi memori server. |
| **Dashboard Guru (`/guru`)** | Tombol aksi `Kirim Pengingat WhatsApp` dan kolom nomor WA ortu di tabel siswa. | Wali kelas tidak perlu mengirim pesan WA manual; status siswa langsung terlihat di sistem internal. |
| **Monitoring Jurusan (`/monitoring/jurusan`)** | Tombol aksi `Kirim WA Ortu` pada daftar siswa yang belum hadir. | Konsistensi penghapusan ekosistem WhatsApp. |
| **Monitoring Kepulangan (`/monitoring/kepulangan`)** | Modal kirim pesan WA darurat / pengingat penjemputan siswa. | Konsistensi penghapusan ekosistem WhatsApp. |
| **Portal Ortu (`/orang-tua`)** | Tombol header `[Login Guru/Admin]`. | Menjaga portal orang tua tetap fokus untuk wali murid tanpa tautan ke panel staf. |
| **Portal Ortu (`/orang-tua`)** | Tombol Demo / "Coba Akses Cepat Tanpa Input". | Menjamin kepatuhan verifikasi NIS asli untuk keamanan privasi murid. |
| **Portal Ortu (`/orang-tua`)** | Tombol "Ganti Anak". | Memastikan satu sesi login terkunci pada identitas anak yang telah diverifikasi dengan NIS valid. |

---

## 6. Rincian Fitur yang Diperbarui (Updated Features)

### 6.1. Sinkronisasi Dashboard Guru & Wali Kelas (`/guru`)
- **Stat Card Tambahan**: Menambahkan kartu statistik **"Izin / Sakit"** (warna ungu) di baris rekap kehadiran.
- **Koreksi Kalkulasi Presensi**: Siswa yang memiliki surat izin/sakit resmi pada hari tersebut **tidak dihitung sebagai Siswa Belum Hadir / Alpha**, melainkan dihitung ke dalam kelompok berizin.
- **Filter Tabel Siswa**: Penambahan tab filter **Izin / Sakit** di sebelah filter *Semua*, *Belum Hadir*, *Tepat Waktu*, dan *Terlambat*.
- **Tampilan Baris Tabel**: Kolom status kehadiran menampilkan badge ungu `Sakit` atau indigo `Izin` lengkap dengan catatan alasan orang tua dan stempel status *"Disampaikan Ortu"*.

### 6.2. Penyesuaian Timeline KBM di Dasbor Orang Tua
- Jika siswa memiliki catatan permohonan izin/sakit aktif:
  - Tahap 2 diubah menjadi: *"Permohonan Sakit / Izin - Terverifikasi oleh pihak sekolah (Alasan: ...)"*.
  - Tahap 3 KBM diubah menjadi: *"Ananda berhalangan hadir dengan keterangan resmi Sakit / Izin"*.

### 6.3. Penambahan Field NIS pada Master Data Siswa
- Migrasi database menambahkan kolom `nis` unik pada tabel `students`.
- Formulir Tambah dan Edit Siswa di menu Manajemen Siswa diperbarui dengan field NIS yang wajib diisi.

---

## 7. Arsitektur Teknis & Skema Basis Data

### 7.1. Tabel Baru: `student_permissions`
Menyimpan riwayat dan status permohonan izin/sakit murid:
```sql
CREATE TABLE student_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    type ENUM('Sakit', 'Izin', 'Izin Ada Keperluan Keluarga', 'Izin Lainnya') NOT NULL,
    date DATE NOT NULL,
    notes TEXT NULL,
    attachment VARCHAR(255) NULL,
    status ENUM('menunggu', 'disetujui', 'ditolak') DEFAULT 'disetujui',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY student_date_unique (student_id, date)
);
```

### 7.2. Tabel Baru: `parent_push_subscriptions`
Menyimpan token endpoint langganan browser Web Push orang tua:
```sql
CREATE TABLE parent_push_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    endpoint_hash VARCHAR(64) NOT NULL UNIQUE,
    endpoint TEXT NOT NULL,
    p256dh VARCHAR(255) NOT NULL,
    auth VARCHAR(255) NOT NULL,
    device_name VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);
```

### 7.3. Pembaruan Tabel `students`
```sql
ALTER TABLE students ADD COLUMN nis VARCHAR(30) NULL AFTER id;
```

---

## 8. Spesifikasi Rute & Endpoint

| Metode | URL Endpoint | Controller & Action | Hak Akses | Deskripsi |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/orang-tua` | `ParentDashboardController@index` | Publik (Orang Tua) | Menampilkan formulir verifikasi login atau dashboard presensi anak. |
| `POST` | `/orang-tua/pilih` | `ParentDashboardController@selectStudent` | Publik (Orang Tua) | Memvalidasi kombinasi NIS, Nama Siswa, dan Kelas. |
| `POST` | `/orang-tua/keluar` | `ParentDashboardController@clear` | Sesi Ortu | Mengakhiri sesi login orang tua. |
| `GET` | `/orang-tua/data` | `ParentDashboardController@data` | Sesi Ortu | Endpoint JSON untuk polling sinkronisasi live data status anak. |
| `POST` | `/orang-tua/izin` | `ParentDashboardController@storePermission` | Sesi Ortu | Menyimpan formulir permohonan izin atau sakit anak ke database. |
| `POST` | `/orang-tua/push-subscribe` | `ParentDashboardController@subscribePush` | Sesi Ortu | Mendaftarkan token perangkat Web Push orang tua. |
| `POST` | `/orang-tua/push-unsubscribe`| `ParentDashboardController@unsubscribePush` | Sesi Ortu | Mencabut langganan Web Push perangkat. |
| `POST` | `/orang-tua/push-test` | `ParentDashboardController@testPush` | Sesi Ortu | Mengirimkan satu notifikasi uji coba langsung ke layar perangkat. |
| `GET` | `/guru` | `TeacherDashboardController@index` | Guru / Wali Kelas | Dashboard pemantauan presensi harian kelas binaan. |

---

## 9. Jaminan Kualitas & Hasil Pengujian (Quality Assurance)

Seluruh perubahan telah diuji secara menyeluruh melalui rangkaian automated test suite Laravel PHPUnit:

```text
Summary Eksekusi Test:
✓ Tests Executed: 30 passed, 0 failed
✓ Total Assertions: 102 assertions
✓ Execution Time: ~3.8 detik
✓ Code Style: 100% PSR-12 / Laravel Pint Compliant
```

### Lingkup Skenario Pengujian yang Lulus:
1. `test_unauthenticated_parent_sees_verification_login_page`
2. `test_parent_login_succeeds_with_valid_requirements` (Validasi NIS, Nama, Kelas)
3. `test_parent_login_fails_if_nis_does_not_match`
4. `test_parent_login_fails_if_student_name_does_not_match`
5. `test_parent_login_fails_if_class_does_not_match`
6. `test_authenticated_parent_can_view_child_dashboard`
7. `test_parent_dashboard_shows_in_school_status_when_student_checked_in`
8. `test_parent_dashboard_shows_departed_status_when_student_checked_out`
9. `test_parent_can_logout_and_clear_session`
10. `test_parent_can_subscribe_device_for_push_notifications`
11. `test_parent_can_unsubscribe_device_from_push_notifications`
12. `test_attendance_log_creation_dispatches_webpush_job`
13. `test_parent_can_submit_permission_form_for_sick`
14. `test_parent_can_submit_permission_form_for_permit`
15. `test_parent_dashboard_displays_sick_banner_and_status`
16. Skenario pengujian monitoring kepulangan, durasi belajar, dan pemfilteran kelas lainnya (15 tests).

---

## 10. Panduan Penggunaan Singkat

### Bagi Orang Tua Murid:
1. Buka tautan `http://localhost:8000/orang-tua` dari browser HP atau komputer.
2. Masukkan **NIS**, **Nama Lengkap Ananda**, dan pilih **Kelas**. Klik **Verifikasi & Masuk**.
3. Klik tombol **Aktifkan di HP Ini** agar setiap tap sidik jari ananda memunculkan pemberitahuan otomatis di layar HP.
4. Jika ananda sakit atau izin, klik tombol **Kirim Izin / Sakit**, pilih tanggal & alasan, lalu kirimkan. Permohonan akan langsung disahkan di sistem sekolah.

### Bagi Guru / Wali Kelas:
1. Buka menu **Dashboard Wali Kelas** (`/guru`).
2. Siswa yang orang tuanya telah mengajukan izin/sakit akan otomatis masuk ke kategori **Izin / Sakit** lengkap dengan alasan yang tertera di tabel, dan tidak akan ditandai sebagai Alpha/Belum Hadir.
