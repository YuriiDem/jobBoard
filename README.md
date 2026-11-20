# Niche Job Board

Single-tenant Laravel 12 starter using PostgreSQL 16, Redis, Blade and Filament 3. The current checkpoint includes the installer and admin login. See [ARCHITECTURE.md](ARCHITECTURE.md) for public-directory and SEO milestones.

## Run with Docker

From this directory, run:

```sh
docker compose up -d --build
```

Open http://localhost:8080/admin. To create your first owner and optionally seed demonstration data:

```sh
docker compose run --rm app php artisan jobboard:install
```

The installer prompts for the niche, site name, currency and first owner. Reruns preserve the owner password. Demo jobs are drafts with placeholder application links. The seven demonstration history dates start with zero active vacancies.

For automated configuration, copy `.env.example` to `.env`. Set `BOARD_NICHE`, `BOARD_NAME`, `BOARD_CURRENCY`, optional comma-separated `FEATURED_TAGS` and `BOARD_MARKETS`, and set `BOARD_DEMO=true` to seed. Run Compose again to apply changes. Values passed through the host environment file take precedence over interactive settings saved inside the state volume. For unattended owner creation, set `BOARD_ADMIN_EMAIL` and `BOARD_ADMIN_PASSWORD_FILE` and mount a readable secret file at that path in the init container.

Set `APP_URL` to the public origin and `BOARD_PORT` to the desired loopback port. Put your TLS reverse proxy in front of Nginx for deployment. Compose provisions a private database password, application key and volumes for database, Redis and installation state. Preserve and back up those volumes. The worker and scheduler run automatically. Inspect startup with `docker compose logs init app web`. For Artisan commands use `docker compose run --rm app php artisan ...` to load runtime secrets.

## Native development

Install PHP 8.3+ with PostgreSQL and Redis extensions, Composer, and Node 22+. Configure a dedicated PostgreSQL 16+ database and Redis. Copy `.env.example` to `.env`, set database credentials, then run `composer install`, `npm ci`, `npm run build` and `php artisan jobboard:install`. Run `php artisan serve` for HTTP. Run `php artisan queue:work` for the Redis worker. Outside Compose, schedule `php artisan schedule:run` once per minute from the project directory.

Board, theme, pricing and SEO settings are in `config/jobboard.php`; their environment names and defaults are in `.env.example`. Run the installer again after changing configured taxonomies. For foundation tests, configure a separate empty test PostgreSQL database, run migrations there, then run `vendor/bin/phpunit`.
