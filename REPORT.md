# REPORT

Date: 2025-10-30

## Environment & Versions
- PHP: ^8.2 (per composer.json)
- Laravel: ^12.0
- PHPUnit: ^11.5.3
- Node: project expects modern Node compatible with Vite 7
- Vite: ^7.0.7
- Tailwind: ^4.0.0
- Laravel Vite plugin: ^2.0.0
- DOMPDF package: barryvdh/laravel-dompdf ^3.1

## Setup Steps (Local)
1) Composer deps
   - composer install
2) Env
   - cp .env.example .env
   - php artisan key:generate
   - Set DB_* in .env (defaults use MySQL localhost)
3) Database
   - php artisan migrate
   - Optional: php artisan migrate:fresh --seed
4) Frontend
   - npm install
   - npm run dev
5) Serve
   - php artisan serve

Alternatively, use Makefile:
- make up
- make seed
- make test

## PDF Export
- A placeholder service `App/Domain/Accounting/Services/ReportPdfService` is added but intentionally not configured.
- Any PDF export should call this service; it will throw a runtime exception: "PDF export not configured".
- Action: Configure or pin compatible PDF libs later, then implement the service.

## Known Issues / Notes
- PDF export is disabled (placeholder only).
- Default cache/queue in .env.example use `database` to reduce external deps; Redis can be enabled later if needed.
- APP_TIMEZONE default set to Asia/Bangkok.
