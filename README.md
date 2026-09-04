# Voucher Seat Assignment

Aplikasi kecil untuk keperluan campaign promo maskapai — crew input data penerbangan, sistem bakal ngundi 3 kursi random buat pemenang voucher. Dibuat pakai Laravel di belakang dan React di depan.

Screenshot aplikasinya:
<img width="1620" height="709" alt="image" src="https://github.com/user-attachments/assets/36424f68-02d9-43b9-9e5b-f4fb2616abd7" />

<img width="1623" height="708" alt="image" src="https://github.com/user-attachments/assets/8fe3e81d-17a9-40d9-a670-289bcf058870" />



## Kenapa dibuat begini

Requirement-nya minta ada validasi supaya satu flight number + tanggal yang sama nggak bisa di-generate vouchernya dua kali. Jadi alurnya: cek dulu ke `/api/check`, kalau belum ada baru generate lewat `/api/generate`. Kalau langsung generate tanpa cek dulu, race condition-nya rawan — makanya di level database juga dikasih unique constraint sebagai jaring pengaman, bukan cuma andalin pengecekan di aplikasi.

Untuk generate kursinya sendiri, logic-nya taruh di service class terpisah (`SeatGeneratorService`) biar controller-nya nggak gendut. Cara kerjanya: bikin dulu semua kombinasi kursi yang valid sesuai tipe pesawat (misal ATR cuma A/C/D/F, nggak ada B dan E), baru dari situ diambil 3 secara acak tanpa pengembalian. Jadi otomatis nggak akan pernah keluar kursi yang nggak valid atau duplikat — bukan dicek belakangan, tapi memang dari awal cuma kursi valid yang ada di dalam pool-nya.

## Stack

- Laravel 11 (PHP 8.2+)
- React + Vite
- SQLite (biar simpel, nggak perlu setup database server)

## Struktur folder

```
voucher-project/
├── backend/                 → Laravel API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/VoucherController.php
│   │   │   ├── Requests/           (validasi input)
│   │   │   └── Resources/          (format response JSON)
│   │   ├── Exceptions/VoucherAlreadyExistsException.php
│   │   ├── Models/Voucher.php
│   │   └── Services/SeatGeneratorService.php
│   ├── database/migrations/
│   ├── routes/api.php
│   └── tests/Feature/VoucherApiTest.php
├── frontend/                → React app
│   └── src/App.jsx
├── docker-compose.yml
└── README.md
```

## Cara jalanin

### Yang perlu disiapin dulu

- PHP 8.2 ke atas
- Composer
- Node.js 18+ sama npm
- Docker (opsional, kalau males install PHP/Node manual)

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

Kalau lancar, API-nya jalan di `http://localhost:8000`.

### Frontend

Buka terminal baru:

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

Buka `http://localhost:5173` di browser.

### Jalanin test (kalau mau)

```bash
cd backend
php artisan test
```

### Atau pakai Docker aja

```bash
docker-compose up
```

Ini bakal jalanin backend dan frontend sekaligus, dua-duanya. Untuk setup yang lebih niat lagi sebenarnya bisa pakai Laravel Sail, tapi buat kebutuhan sekarang docker-compose biasa udah cukup.

## Endpoint API

### Cek voucher sudah ada atau belum

`POST /api/check`

```json
{
  "flightNumber": "GA102",
  "date": "2025-07-12"
}
```

Balikannya:
```json
{ "exists": true }
```

### Generate voucher

`POST /api/generate`

```json
{
  "name": "Sarah",
  "id": "98123",
  "flightNumber": "ID102",
  "date": "2025-07-12",
  "aircraft": "Airbus 320"
}
```

Kalau berhasil (201):
```json
{ "success": true, "seats": ["3B", "7C", "14D"] }
```

Kalau ternyata udah pernah di-generate sebelumnya (409):
```json
{ "success": false, "message": "Vouchers have already been generated for flight ID102 on 2025-07-12." }
```

Kalau ada input yang salah/kosong (422), Laravel bakal balikin detail error-nya per field.

## Catatan / hal yang mungkin masih bisa dibenerin

- Belum ada auth sama sekali — di luar scope assessment ini sih, tapi kalau mau dipakai beneran ya wajib ditambahin.
- Seat pool-nya di-generate ulang tiap kali ada request, bukan di-cache. Buat jumlah pesawat yang cuma 3 tipe kayak sekarang nggak masalah, tapi kalau tipe pesawatnya banyak banget mungkin worth dipikirin caching-nya.
- Frontend belum ada automated test, cuma dicek manual aja alur check → generate-nya sama error state-nya, dan sejauh ini aman.
