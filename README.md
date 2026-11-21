# Job Board

A fast, SEO-optimized job board starter. Server-rendered job listings include dedicated pages for technologies, roles and locations, with canonical URLs, structured data and XML sitemaps.

## Stack

Laravel 12, Blade, PostgreSQL 16 and Redis. The interface uses Tailwind CSS 4, daisyUI 5, Alpine.js and HTMX. Filament 3 powers the admin CMS, with Peek for homepage previews.

## Quick start

From the repository root, start the app with Docker Compose:

```sh
docker compose up -d --build
```

Open [the local job board](http://localhost:8080). To configure the board and create the first administrator, run:

```sh
docker compose run --rm app php artisan jobboard:install
```

Generate a private sign-in link, then open it within 15 minutes and enter your admin credentials:

```sh
docker compose run --rm --no-deps app php artisan jobboard:admin-login
```

In the admin panel, publish a vacancy to list it at `/jobs`. Open **CMS → Homepage** to edit and preview the homepage sections.
