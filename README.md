# POS

Point of Sale application built with Laravel, Livewire, and Filament.

## Requirements

- PHP 8.3+
- Node.js 18+
- MySQL 8.0 (via Docker)

## Development Setup

### 1. Start MySQL

```bash
docker compose up -d
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Setup environment

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Run database migration

```bash
php artisan migrate
```

### 5. Start all dev services

You need **3 terminals** running simultaneously:

```bash
# Terminal 1 — Laravel dev server
php artisan serve
```

```bash
# Terminal 2 — Vite (frontend hot reload)
npm run dev
```

```bash
# Terminal 3 — Reverb (WebSocket for real-time stock sync)
php artisan reverb:start
```

### 6. Open in browser

- **POS (Staff):** [http://localhost:8000/kasir](http://localhost:8000/kasir)
- **Admin Panel:** [http://localhost:8000/admin](http://localhost:8000/admin)
- **phpMyAdmin:** [http://localhost:8080](http://localhost:8080)

## Production (Docker)

```bash
docker compose -f docker-compose.prod.yaml up -d --build
docker compose -f docker-compose.prod.yaml exec app php artisan migrate --force
```

App runs on port `8080`, Reverb WebSocket on port `6001`.

## License

MIT
