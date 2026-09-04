# Voucher Seat Assignment Application

A web application for an airline promotional campaign that randomly assigns
3 unique seat numbers to voucher winners, built with **Laravel** (backend
REST API) and **React** (frontend).

## Tech Stack

- **Backend:** PHP 8.2+, Laravel 11
- **Frontend:** React 18 + Vite
- **Database:** SQLite

## Project Structure

```
project/
├── backend/          # Laravel application (REST API)
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/VoucherController.php
│   │   │   ├── Requests/CheckVoucherRequest.php
│   │   │   ├── Requests/GenerateVoucherRequest.php
│   │   │   └── Resources/VoucherResource.php
│   │   │   └── Resources/VoucherCheckResource.php
│   │   ├── Exceptions/VoucherAlreadyExistsException.php
│   │   ├── Models/Voucher.php
│   │   └── Services/SeatGeneratorService.php
│   ├── database/migrations/
│   ├── routes/api.php
│   └── tests/Feature/VoucherApiTest.php
├── frontend/         # React application
│   └── src/App.jsx
├── docker-compose.yml
└── README.md
```

## Prerequisites

- PHP 8.2 or higher
- Composer 2.x
- Node.js 18+ and npm
- (Optional) Docker & Docker Compose, if you prefer the containerized setup

## Setup & Run — Manual (without Docker)

### 1. Backend (Laravel)

```bash
cd backend

# Install PHP dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Create the SQLite database file
touch database/database.sqlite

# Run migrations (creates the `vouchers` table)
php artisan migrate

# Start the API server
php artisan serve
```

The API will be available at `http://localhost:8000`.

### 2. Frontend (React)

Open a new terminal:

```bash
cd frontend

# Install dependencies
npm install

# Configure environment
cp .env.example .env
# (default already points to http://localhost:8000/api, adjust if needed)

# Start the dev server
npm run dev
```

The app will be available at `http://localhost:5173`.

### 3. Run tests (optional but recommended)

```bash
cd backend
php artisan test
```

## Setup & Run — with Docker

From the project root:

```bash
docker-compose up
```

This starts both the backend (`http://localhost:8000`) and frontend
(`http://localhost:5173`) containers, installing dependencies, running
migrations, and starting both dev servers automatically.

> Note: the Docker setup uses plain `php:8.2-cli` and `node:20-alpine`
> images with inline provisioning for simplicity. For a more
> production-like setup, consider using **Laravel Sail**
> (`composer require laravel/sail --dev && php artisan sail:install`).

## API Endpoints

### `POST /api/check`

Checks whether voucher assignments already exist for a given flight and date.

**Request:**
```json
{
  "flightNumber": "GA102",
  "date": "2025-07-12"
}
```

**Response:**
```json
{ "exists": true }
```

### `POST /api/generate`

Generates 3 random, unique, aircraft-valid seats and persists the assignment.

**Request:**
```json
{
  "name": "Sarah",
  "id": "98123",
  "flightNumber": "ID102",
  "date": "2025-07-12",
  "aircraft": "Airbus 320"
}
```

**Response (success, HTTP 201):**
```json
{ "success": true, "seats": ["3B", "7C", "14D"] }
```

**Response (duplicate, HTTP 409):**
```json
{ "success": false, "message": "Vouchers have already been generated for flight ID102 on 2025-07-12." }
```

**Response (validation error, HTTP 422):**
```json
{ "success": false, "message": "The given data was invalid.", "errors": { "aircraft": ["..."] } }
```

## Design Notes

- **Seat generation** lives in `App\Services\SeatGeneratorService`, decoupled
  from the controller. It builds the full valid seat pool for the given
  aircraft type (row range × seat letters, per the spec's seat layout
  reference), then picks 3 unique seats from that pool using `array_rand`
  without replacement — guaranteeing both uniqueness and aircraft validity
  by construction (an invalid seat like `5B` on an ATR can never be
  generated, since it's never added to the pool).
- **Duplicate prevention** is enforced at two levels: an application-level
  check in the controller (matches the `/api/check` contract), and a
  **database-level unique constraint** on `(flight_number, flight_date)` as
  a safety net against race conditions (e.g. two simultaneous requests for
  the same flight/date).
- **Validation** is handled via Laravel **Form Request** classes
  (`CheckVoucherRequest`, `GenerateVoucherRequest`) with custom error
  messages, keeping controllers thin.
- **Response formatting** uses Laravel **API Resources**
  (`VoucherResource`, `VoucherCheckResource`) for consistent JSON shapes.
- **Error handling** uses a custom `VoucherAlreadyExistsException` (mapped
  to HTTP 409) registered in `bootstrap/app.php`, plus a consistent JSON
  shape for validation errors (HTTP 422).
- **Feature tests** (`tests/Feature/VoucherApiTest.php`) cover: checking
  existing/non-existing vouchers, required-field validation on both
  endpoints, successful generation with unique/valid seats (including an
  ATR-specific check that only `A/C/D/F` letters within rows 1–18 are ever
  produced), aircraft type validation, and duplicate-assignment rejection.

## Known Limitations / Possible Improvements

- No authentication layer — out of scope per the assessment brief.
- The seat pool is rebuilt on every request rather than cached; for the
  scale of this application (a handful of aircraft types, small pools),
  this has no meaningful performance impact.
- Frontend has no automated tests (not required by the brief), but manual
  testing confirms the check → generate flow and error states work
  end-to-end.
