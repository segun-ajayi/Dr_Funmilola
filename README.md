# Dr. Funmilola Wuraola — Breast Oncology Practice Platform

Laravel 13 + React/TypeScript application for a specialist breast oncology practice, patient access, appointments, academic content and safe visual content management.

## Local development

1. Copy `.env.example` to `.env` and generate an application key.
2. Use SQLite locally or configure PostgreSQL 17+.
3. Run `php artisan migrate --seed`.
4. Run `npm install` and `npm run dev`.
5. Start Laravel with `php artisan serve`.

Development accounts use the `example.test` domain and the seed-only password `ChangeMe!2026`. Never use these credentials outside development.

See [docs/MASTER_PLAN.md](docs/MASTER_PLAN.md) for scope, task status, acceptance criteria and mobile roadmap.
