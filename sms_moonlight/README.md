# ZenCraft CSMS SaaS Application

This Laravel 12 application contains the ZenCraft SaaS control plane and the tenant-enabled SMS Moonlight school application. It provides administration tools and dedicated student and teacher portals for managing school operations.

## SaaS foundation

- Central platform authentication and dashboard
- Plans, subscriptions, trials, and school lifecycle state
- Automated database-per-school provisioning
- Domain-based tenant resolution
- Isolated tenant storage and queue context
- Initial school administrator provisioning

## Main features

- Student, teacher, staff, class, subject, and school-year management
- Junior high school and college enrollment workflows
- Attendance recording, RFID support, reporting, and synchronization APIs
- Grades, quizzes, assignments, and submission controls
- Student payments and configurable payment types
- Announcements, role-based administration, and portal authentication
- PDF, spreadsheet, QR-code, and import/export workflows

## Requirements

- PHP 8.2 or later
- Composer
- Node.js and npm
- SQLite, MySQL, or another Laravel-supported database

## Setup

Run the following commands from this folder:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

On Windows PowerShell, create the environment file with:

```powershell
Copy-Item .env.example .env
```

Configure the database and other environment values in `.env` before running migrations. The local `.env` file and database files are intentionally ignored by Git.

## Development

Start the application services with:

```bash
composer run dev
```

The default local application URL is `http://127.0.0.1:8000`.

## Tests

```bash
composer test
```

## Useful documentation

Project guides, test plans, and database cleanup scripts are available in [`deliverables`](./deliverables/).
