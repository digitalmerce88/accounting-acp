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

## Admin Users Management
- Admin can manage user roles at `/admin/users` (requires role: admin).
- UI built with Inertia/Vue; toggle role checkboxes and click Save per user.
- API endpoints:
   - GET /admin/users (JSON or Inertia)
   - PATCH /admin/users/{user}/roles (payload: { roles: string[] slugs })

## Admin Dashboard
- Landing at `/admin` shows dashboard with metrics and recents:
   - accounts_count, journals_count, trial balance totals (DR/CR)
   - recent 5 journals (date, memo, status)
- JSON also available at `/admin` for automation/tests

## Demo Accounts
- Seeders create demo users with roles:
   - admin@example.com / password (role: admin)
   - accountant@example.com / password (role: accountant)
   - viewer@example.com / password (role: viewer)
- To seed: `php artisan migrate:fresh --seed` or `php artisan demo:seed-accounting`

## Verification (2025-10-30)
- Database: migrate:fresh --seed => PASS
- Tests: 39 passed, 0 failed => PASS
- Build: Vite production build recently succeeded => PASS

## Next Steps (optional)
- Implement real PDF export in `ReportPdfService` (pin dompdf or alternative and wire controllers).
- Enhance Accounts/Journals UI (forms, validation messages, pagination, search).
