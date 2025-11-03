# Oftalmo App

A Laravel-based ophthalmology management application. This guide walks you through setting up the project locally either with your native PHP environment or using [Laravel Sail](https://laravel.com/docs/sail).

## 1. Prerequisites

Make sure the following tools are installed before you begin:

- **PHP**: 8.2 or newer with the required extensions for Laravel (BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML).
- **Composer**: to install PHP dependencies.
- **Node.js**: 18 or newer, plus **npm** (comes bundled) for frontend assets.
- **Database**: MySQL 8.x (or a compatible service).
- **Git**: to clone the repository.
- **Optional – Docker & Docker Compose**: if you prefer using Laravel Sail.

## 2. Clone the repository

```bash
git clone <repository-url>
cd oftalmo-app
```

## 3. Environment configuration

1. Duplicate the example environment file and update it with your local settings:
   ```bash
   cp .env.example .env
   ```
2. Generate an application key:
   ```bash
   php artisan key:generate
   ```
3. Update the database credentials in `.env` (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
4. Configure any additional services (mail, storage, etc.) the project requires in `.env`.

## 4. Install dependencies

```bash
composer install
npm install
```

## 5. Database migration and seeding

Run the migrations to create the database schema and seed the initial data (roles, permissions, sample patients, etc.):

```bash
php artisan migrate --seed
```

If you only need the schema, omit `--seed`.

## 6. Run the development servers

### Backend (PHP)

Start the Laravel development server:

```bash
php artisan serve
```

By default the application will be available at `http://127.0.0.1:8000`.

### Frontend assets

Compile frontend assets with Vite. For hot-reloading during development:

```bash
npm run dev
```

For a production build:

```bash
npm run build
```

## 7. Using Laravel Sail (Docker alternative)

If you prefer Docker, install Sail dependencies after copying `.env`:

```bash
composer install
php artisan sail:install
./vendor/bin/sail up -d
```

Sail will boot the application, MySQL, and phpMyAdmin (available at `http://localhost:8081`). Use Sail to run artisan, composer, npm, and other commands by prefixing them with `./vendor/bin/sail` (e.g., `./vendor/bin/sail artisan migrate`).

## 8. Running automated tests

Run the PHPUnit test suite to verify the installation:

```bash
php artisan test
```

If you are using Sail:

```bash
./vendor/bin/sail test
```

## 9. Troubleshooting tips

- Ensure the database service is running and accessible with the credentials provided in `.env`.
- If migrations fail, verify that the configured database exists and your user has permission to create tables.
- When frontend assets do not refresh, stop any existing Vite instance and re-run `npm run dev`.
- Clear cached configuration or compiled files if the app behaves unexpectedly:
  ```bash
  php artisan optimize:clear
  ```

## 10. Additional resources

- [Laravel Documentation](https://laravel.com/docs)
- [Vite Documentation](https://vitejs.dev/guide/)
- [Laravel Sail Documentation](https://laravel.com/docs/sail)

You are now ready to start developing the Oftalmo App!
