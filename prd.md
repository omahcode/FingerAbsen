Tentu. Karena X1000-C kamu **sudah berhasil diakses melalui LAN `192.168.16.179:4370` dan sudah berhasil dibaca menggunakan `pyzk`**, PRD-nya bisa dibuat lebih spesifik, bukan sekadar konsep.

# PRD — Sistem Monitoring & Manajemen Fingerprint X1000-C

**Versi:** 1.0
**Platform:** Web Application
**Target:** PC/Server lokal
**Status:** Development Planning

## 1. Ringkasan Produk

Sistem ini adalah aplikasi web untuk **memonitor dan mengelola mesin fingerprint Solution X1000-C melalui jaringan LAN**.

Aplikasi akan mengambil data dari mesin fingerprint melalui TCP/IP, menyimpan data ke database, kemudian menampilkannya dalam dashboard web.

Tujuan utamanya adalah menggantikan proses manual seperti mengambil data menggunakan USB atau membuka software fingerprint satu per satu.

### Arsitektur

```text
                    LAN
                     │
                     ▼
        ┌─────────────────────────┐
        │ Solution X1000-C        │
        │ 192.168.16.179:4370     │
        └────────────┬────────────┘
                     │
                     ▼
        ┌─────────────────────────┐
        │ Fingerprint Service     │
        │ Python + pyzk           │
        └────────────┬────────────┘
                     │
                     ▼
        ┌─────────────────────────┐
        │       MySQL             │
        └────────────┬────────────┘
                     │
                     ▼
        ┌─────────────────────────┐
        │ Laravel Web Application │
        │ Dashboard & Management  │
        └─────────────────────────┘
```

---

# 2. Tujuan

### Tujuan utama

1. Memantau status mesin fingerprint dari web.
2. Mengambil data absensi dari X1000-C secara otomatis.
3. Menyimpan data absensi ke database.
4. Mengelola data pengguna fingerprint.
5. Menampilkan absensi secara realtime/near-realtime.
6. Membuat laporan absensi.
7. Mengurangi ketergantungan terhadap USB.
8. Memungkinkan pengelolaan beberapa mesin fingerprint dalam satu dashboard.

---

# 3. Target Pengguna

### Admin

Memiliki akses penuh terhadap sistem.

Dapat:

* mengelola mesin
* mengelola user
* melihat semua absensi
* melakukan sinkronisasi
* mengatur sistem
* membuat laporan

### Operator

Dapat:

* melihat dashboard
* melihat absensi
* mengelola user
* melakukan sinkronisasi
* membuat laporan

### Viewer

Hanya dapat:

* melihat dashboard
* melihat data absensi
* melihat laporan

---

# 4. Modul Sistem

## A. Dashboard

Dashboard menjadi halaman utama setelah login.

Menampilkan:

```text
┌──────────────────────────────────────────┐
│ FINGERPRINT MONITORING                   │
├──────────────────────────────────────────┤
│                                          │
│  MESIN ONLINE       USER       ABSENSI   │
│       1             125          87      │
│                                          │
├──────────────────────────────────────────┤
│ ABSENSI TERBARU                          │
│                                          │
│ 07:31  Budi Santoso       ✓ Hadir       │
│ 07:33  Andi               ✓ Hadir       │
│ 07:45  Sinta              ⚠ Terlambat   │
│                                          │
└──────────────────────────────────────────┘
```

### Widget

* Total mesin
* Mesin online
* Mesin offline
* Total user
* Absensi hari ini
* Hadir
* Terlambat
* Pulang
* Tidak hadir
* Absensi terbaru

---

# 5. Modul Device / Mesin

Digunakan untuk mengelola X1000-C.

### Data mesin

```text
Nama Mesin
IP Address
Port
Device ID
Serial Number
Firmware
Lokasi
Status
Last Sync
```

Contoh:

```text
Nama       : Fingerprint Ruang TU
IP         : 192.168.16.179
Port       : 4370
Status     : 🟢 Online
Last Sync  : 14:32:15
```

### Fitur

* Tambah mesin
* Edit mesin
* Hapus mesin
* Test koneksi
* Connect
* Disconnect
* Sync user
* Sync attendance
* Sync waktu
* Refresh status

---

# 6. Modul User

Menampilkan seluruh pengguna yang terdapat pada mesin/database.

### Data

```text
User ID
Nama
UID
Nomor Fingerprint
Privilege
Status
Mesin
Tanggal dibuat
```

Contoh:

```text
ID       Nama             Mesin
001      Budi Santoso     X1000-C
002      Andi Wijaya      X1000-C
003      Sinta             X1000-C
```

### Fitur

* Tambah user
* Edit user
* Hapus user
* Search
* Filter
* Import
* Export
* Sync dari mesin
* Sync ke mesin

---

# 7. Modul Attendance

Ini merupakan modul utama.

Setiap transaksi fingerprint dari X1000-C akan disimpan.

### Data attendance

```text
ID
User ID
Nama
Tanggal
Jam
Status
Device
Source
Created At
```

Contoh:

| Jam   | User  | Status    | Mesin   |
| ----- | ----- | --------- | ------- |
| 07:12 | Budi  | Hadir     | X1000-C |
| 07:15 | Andi  | Hadir     | X1000-C |
| 07:43 | Sinta | Terlambat | X1000-C |

### Fitur

* Filter tanggal
* Filter user
* Filter mesin
* Search
* Sorting
* Detail attendance
* Export Excel
* Export PDF

---

# 8. Monitoring Realtime

Dashboard melakukan pengecekan terhadap mesin secara berkala.

Contoh:

```text
🟢 X1000-C ONLINE

Last Connection:
14:32:21

Last Attendance:
14:31:59
```

Ketika seseorang fingerprint:

```text
┌──────────────────────────────┐
│ 🔔 ABSENSI BARU              │
├──────────────────────────────┤
│ BUDI SANTOSO                 │
│ ID       : 0025              │
│ Waktu    : 14:31:59          │
│ Status   : HADIR             │
│ Mesin    : X1000-C           │
└──────────────────────────────┘
```

---

# 9. Fingerprint Service

Ini bagian penting yang menjadi penghubung antara Laravel dan X1000-C.

Service menggunakan:

```text
Python
pyzk
TCP/IP
```

Tugasnya:

### Device Connection

```text
Python
 ↓
192.168.16.179
 ↓
Port 4370
 ↓
X1000-C
```

### Attendance Sync

Service secara berkala:

```text
X1000-C
   ↓
get_attendance()
   ↓
Python
   ↓
MySQL
```

### User Sync

```text
X1000-C
   ↓
get_users()
   ↓
Python
   ↓
MySQL
```

---

# 10. Sinkronisasi

Sistem harus mencegah data absensi menjadi duplikat.

Contoh:

```text
X1000-C
   │
   ├── Attendance #1001
   ├── Attendance #1002
   └── Attendance #1003
             │
             ▼
          Database
```

Jika service melakukan sync lagi:

```text
#1001 → sudah ada → SKIP
#1002 → sudah ada → SKIP
#1003 → sudah ada → SKIP
#1004 → baru → INSERT
```

Dengan demikian database tetap bersih.

---

# 11. Database

Struktur awal:

```text
users
devices
attendance_logs
sync_logs
roles
activity_logs
settings
```

### devices

```text
id
name
ip_address
port
device_id
serial_number
location
status
last_seen_at
created_at
updated_at
```

### users

```text
id
device_user_id
name
card_number
privilege
status
created_at
updated_at
```

### attendance_logs

```text
id
user_id
device_id
attendance_date
attendance_time
status
fingerprint_status
source
created_at
updated_at
```

### sync_logs

```text
id
device_id
type
started_at
finished_at
records
status
error_message
```

---

# 12. Laporan

### Laporan harian

```text
Tanggal: 05 Oktober 2026

Nama             Masuk      Pulang
Budi Santoso     07:12      16:03
Andi Wijaya      07:15      16:01
Sinta            07:43      16:10
```

### Laporan bulanan

Menampilkan:

* Total hadir
* Total terlambat
* Total tidak hadir
* Total izin
* Total pulang
* Persentase kehadiran

### Export

* Excel
* CSV
* PDF

---

# 13. Sistem Login

Login menggunakan:

```text
Email / Username
Password
```

Role:

```text
ADMIN
OPERATOR
VIEWER
```

Admin memiliki akses penuh.

---

# 14. Notifikasi

Sistem dapat memberikan notifikasi:

* Mesin offline
* Mesin kembali online
* Sync berhasil
* Sync gagal
* Attendance baru
* Error koneksi

Contoh:

> 🔴 Fingerprint Ruang TU offline.

atau:

> 🟢 Fingerprint Ruang TU kembali online.

---

# 15. Teknologi

### Backend

**Laravel 11**

```text
PHP 8.2+
Laravel 11
MySQL
```

### Frontend

```text
Blade
Tailwind CSS
Alpine.js
```

Untuk realtime:

```text
Laravel Broadcasting
WebSocket
```

atau tahap awal menggunakan polling.

### Fingerprint Connector

```text
Python 3
pyzk
```

### Database

```text
MySQL 8+
```

---

# 16. Struktur Project

```text
fingerprint-monitoring/
│
├── app/
│   ├── Models/
│   │   ├── Device.php
│   │   ├── User.php
│   │   ├── AttendanceLog.php
│   │   └── SyncLog.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── DashboardController.php
│   │   │   ├── DeviceController.php
│   │   │   ├── UserController.php
│   │   │   └── AttendanceController.php
│   │
│   └── Services/
│
├── database/
│   └── migrations/
│
├── resources/
│   └── views/
│       ├── dashboard/
│       ├── devices/
│       ├── users/
│       ├── attendance/
│       └── reports/
│
├── routes/
│   └── web.php
│
└── fingerprint-service/
    ├── main.py
    ├── config.py
    ├── device.py
    ├── attendance.py
    ├── users.py
    └── requirements.txt
```

---

# 17. Alur Sistem

### Saat aplikasi dijalankan

```text
PC/Server
   ↓
Fingerprint Service
   ↓
Connect X1000-C
   ↓
Check device
   ↓
Sync users
   ↓
Sync attendance
   ↓
MySQL
   ↓
Laravel
   ↓
Dashboard
```

### Saat ada fingerprint baru

```text
Siswa/Pegawai
      ↓
Scan fingerprint
      ↓
X1000-C
      ↓
Fingerprint Service
      ↓
Validasi data
      ↓
Cek duplikat
      ↓
MySQL
      ↓
Dashboard
      ↓
🔔 Absensi baru
```

---

# 18. Keamanan

Sistem harus:

* menggunakan authentication
* menggunakan authorization berdasarkan role
* password di-hash
* validasi input
* CSRF protection
* tidak membuka port fingerprint ke internet
* koneksi mesin hanya melalui jaringan internal
* menyimpan log aktivitas administrator
* mencegah duplicate attendance
* membatasi akses endpoint API

**X1000-C sebaiknya tetap berada di jaringan LAN internal.** Web publik tidak perlu langsung membuka port `4370` ke internet.

---

# 19. MVP — Versi Pertama

Untuk versi pertama, saya sarankan jangan langsung membuat semuanya.

### Tahap 1

**Device Connection**

* tambah mesin
* IP
* port
* test connection
* online/offline
* device information

### Tahap 2

**User**

* ambil user dari mesin
* tampilkan user di web
* sinkronisasi user

### Tahap 3

**Attendance**

* ambil log fingerprint
* simpan MySQL
* tampilkan di web
* anti-duplicate

### Tahap 4

**Dashboard**

* status mesin
* total user
* absensi hari ini
* absensi terbaru

### Tahap 5

**Report**

* filter
* Excel
* PDF
* laporan harian/bulanan

### Tahap 6

**Realtime**

* WebSocket
* notifikasi fingerprint baru
* status mesin realtime

---

# 20. Acceptance Criteria

Sistem dianggap berhasil apabila:

* [ ] Web dapat menambahkan X1000-C.
* [ ] Web dapat melakukan test koneksi.
* [ ] X1000-C terdeteksi sebagai online.
* [ ] Informasi mesin dapat dibaca.
* [ ] User dari mesin dapat diambil.
* [ ] Attendance dari mesin dapat diambil.
* [ ] Attendance tersimpan ke MySQL.
* [ ] Data tidak terduplikasi.
* [ ] Dashboard menampilkan attendance terbaru.
* [ ] Status mesin dapat dipantau.
* [ ] Data dapat difilter berdasarkan tanggal/user/mesin.
* [ ] Data dapat diekspor.
* [ ] Service dapat berjalan otomatis di Windows.
* [ ] Jika mesin mati, sistem menampilkan status offline.
* [ ] Setelah mesin hidup kembali, sistem dapat melakukan sinkronisasi lagi.

---

## Rekomendasi penting untuk proyek kamu

Karena **X1000-C kamu sudah terbukti bisa dibaca menggunakan `pyzk`**, kita tidak perlu menebak-nebak dari awal. Kita bisa langsung membangun berdasarkan koneksi yang sudah berhasil:

```text
192.168.16.179:4370
        ↓
      pyzk
        ↓
Fingerprint Service
        ↓
      MySQL
        ↓
    Laravel 11
        ↓
 Web Monitoring
```

**Urutan implementasi yang paling aman:** `Database → Laravel API → Python Fingerprint Service → Device Management → Attendance Sync → Dashboard → Realtime → Reports`.

Dengan begitu kita bisa mengetes setiap bagian satu per satu dan kalau ada masalah, mudah diketahui apakah masalahnya di **X1000-C, Python connector, database, atau Laravel**.
