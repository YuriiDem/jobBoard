# Core starter architecture and session handoff

## Approved design

Single-tenant, config-driven niche board. Laravel 12, PHP 8.3+, Blade, PostgreSQL 16+, Redis for sessions/cache/queues, Filament 3.2+, Spatie Schema.org and Sitemap. Minimal semantic HTML only. Public assets use Tailwind v4 and Alpine when needed.

```
Visitors -> Nginx -> Laravel/Blade -> PostgreSQL
                      |             -> Redis
Owner -> /admin -> Filament         -> PostgreSQL
Scheduler -> expiration + stats -> Redis queue -> indexing API
SEO controller -> canonical sitemap shards, robots.txt, llms.txt
```

```
Company 1--* Job *--0..1 Department 1--* Role
Job *--0..1 Role
Job *--0..1 primary Location
Job 1--* job_location *--1 Location -> parent Location
Job 1--* JobMetric (job, day)
DailyStat (day); PublicSlug (unique across job and taxonomy); User (owner)
```

```
app/Models, Services, Http/Controllers, Filament/Resources
app/Console/Commands, Jobs, Observers, Providers
config/jobboard.php
database/migrations, factories, seeders
resources/views
routes/web.php, console.php
tests/Feature
docker/, compose.yaml, Dockerfile
```

## Contracts to preserve across sessions

- Configure niche, brand, featured tags, markets, currency (USD), page size (25), expiration (30 days), SEO templates, llms intro, Google credentials, future theme and Stripe prices centrally. Use config in factories, seeds and public copy. Department is the admin category. Include internships and working-student jobs.
- Curated URLs: /jobs, /jobs/{topic-or-location-or-job}, /jobs/{topic}/{location}, /companies/{slug}, /salary-guide. Shared slug ownership. Reverse pair -> 301. Valid empty curated hub -> 200 noindex. Indexable pairs require configured approval and sufficient active jobs. Serve identical content to people and crawlers. Avoid doorway and scaled pages. Aggregate listings with provenance and useful normalized fields.
- Emit one absolute canonical. Use it in internal links and sitemaps. Crawlable unfiltered pagination self-canonical on page 2+, page 1 redirects to the clean URL, invalid pages return 404. Filters use deterministic query parameters, noindex, clean canonical, parameter-specific crawl controls and no sitemap inclusion. Every job is reachable through ordinary links without using a filter. Avoid zero-result refinement links. Paid outbound links are qualified.
- List queries select lightweight columns and eager-load dependencies. Prevent lazy loading outside production. Use simplePaginate. Use native PostgreSQL FTS, GIN and indexed existence checks. Chunk CLI and sitemap work by ID and measure peak RAM and incremental memory above bootstrap (target <32 MiB incremental). Expiration, snapshots and metrics use SQL. Salary guide cached 6 hours, with documented currency and period basis.
- Publish/expire notifications run after commit and skip missing credentials. Atomic view and apply counters by job/day. Active apply redirect is 302 with X-Robots-Tag noindex, nofollow. Mark expired job detail 410. Only emit valid JobPosting markup that matches visible facts.
- Docker target: one command, shared application image, isolated PostgreSQL/Redis, health checks, persistent application key and data, init, web, worker and scheduler. Installer supports interactive use and deployment secrets, optional niche-aware seeds, first admin. README includes quickstart, configuration, scheduler and operations.
- Sources: [faceted navigation](https://developers.google.com/crawling/docs/faceted-navigation), [facet design](https://developers.google.com/search/blog/2014/02/faceted-navigation-best-and-5-of-worst), [spam policies](https://developers.google.com/search/docs/essentials/spam-policies), [canonicalization](https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls), [pagination](https://developers.google.com/search/docs/specialty/ecommerce/pagination-and-incremental-page-loading).

## Session checkpoint

Sessions 1–4 originated in `../kelp-core-starter` on `jobboard/core-starter`. Release verification used the standalone `jobBoard` checkout on `master`, including its local route edits.

1. **Foundation dependencies complete.** The Laravel 12 and Filament 3 lockfiles install in this worktree. The data model and taxonomy resolver are available for directory work.
2. **Foundation tested.** Compose starts Nginx, PHP-FPM, a worker, a scheduler, PostgreSQL 16 and Redis. Its one-shot installer creates persistent credentials and applies migrations. The native and container installer supports configurable branding, draft demo jobs and first-owner creation. The dedicated queue table is queue_jobs. The default public root currently contains the configured site name. The authenticated admin dashboard and the non-owner denial path have test coverage.
   - Verified composer install, npm ci and npm run build, a fresh Compose boot, all four PostgreSQL migrations, a repeat install and an HTTP 200 from the admin login page.
   - PHPUnit passed four tests with 17 assertions against a separate temporary PostgreSQL 16 database. Coverage includes the real Filament login component, repeated setup, demo row counts, customized currency and taxonomy slugs.
   - Local verification stack uses Compose project kelp-foundation on loopback port 18761. A regular fresh install uses port 8080. See README for credentials and commands.
3. **Public directory and SEO complete.** Blade serves job, taxonomy, company and salary pages. Job filters use canonical query ordering and PostgreSQL full-text search. Job details link to related hubs and record daily views and apply clicks. Robots, llms and live sitemap routes are available. Scheduled sitemap publication remains in session 4.
   - Verified with a disposable PostgreSQL 16 database: `php artisan migrate --no-interaction`, `php vendor/bin/pint --dirty --format agent`, `php vendor/bin/phpunit`, and `git diff --check`. All 9 tests passed with 86 assertions, including a directory query-count ceiling, shared slug collisions, pagination, pair eligibility, expired jobs, counters and crawler routes.
4. **Background work and admin complete.** The scheduler expires jobs every minute, snapshots the market daily at 23:55, and publishes the sitemap hourly. The daily USD median uses the advertised annual salary minimum. The publisher stores immutable shards in shared cache before replacing the index. Salary cache invalidation and indexing dispatch follow transaction commits. Set `GOOGLE_INDEXING_CREDENTIALS` to a readable service-account JSON file on the app, scheduler and worker to enable notifications.
   - Owners can manage jobs, companies, departments, roles and locations. Feature tests exercise Filament forms, access denial, eager-loaded job table relationships, queue rollback, stale notification retries, and published sitemap eligibility.
   - Verified on a disposable PostgreSQL 16 database with `php artisan migrate --no-interaction`, `php vendor/bin/pint --dirty --format agent`, `php vendor/bin/phpunit`, `php artisan schedule:list --no-interaction` and `git diff --check`. All 14 tests passed with 175 assertions. Google delivery was tested with a simulated token and HTTP endpoint.
   - In separate native PHP 8.4 processes with PostgreSQL 16 and Redis 7, 6,000 job rows included 1,500 due for expiration. Running `jobboard:expire`, `jobboard:snapshot` and `jobboard:sitemap` through the bootstrapped Artisan kernel succeeded. Peak allocated PHP memory was 40, 40 and 42 MiB respectively, with increases of 8, 8 and 10 MiB above bootstrap. Release verification against the built Compose images remains in session 5.
   - Session 4 recheck in the standalone `jobBoard` checkout on `master` at `f028102`, with local edits to `routes/web.php` present. Using PHP 8.4.21 and a disposable PostgreSQL 16 instance, `php artisan migrate --no-interaction` reported the schema current and `php vendor/bin/phpunit` passed all 14 tests with 175 assertions. `php artisan schedule:list --no-interaction` listed all three schedules above and `git diff --check` passed.
5. **Release verification complete.**
   - With `BOARD_PORT=18765`, `docker compose -p jobboard-s5-82da up -d --build --wait --wait-timeout 120` built both images and created four fresh volumes. `docker compose -p jobboard-s5-82da ps -a` showed healthy web, PostgreSQL and Redis services, running PHP-FPM, worker and scheduler processes, and successful credentials and init exits. HTTP GET returned 200 for `/up`, `/jobs`, `/companies`, `/salary-guide`, `/admin/login` and `/sitemap.xml`.
   - Created a separate PostgreSQL database with `docker compose -p jobboard-s5-82da exec -T db createdb -U board suite`. In a disposable container from the built app image, ran `composer install --no-interaction --prefer-dist`, `php artisan migrate --no-interaction` and `php vendor/bin/phpunit --do-not-cache-result` with the testing environment, database `suite`, array cache and sessions, synchronous queue and `APP_URL=http://localhost`. PHP 8.4.27 passed all 14 tests with 175 assertions. Google requests used the test HTTP fake.
   - Stopped the isolated scheduler before loading 6,000 published jobs for one company, including 1,500 due to expire. Ran `jobboard:expire`, `jobboard:snapshot` and `jobboard:sitemap` sequentially through separate PHP processes in the release app container with PostgreSQL and Redis. Each process recorded allocated memory after kernel bootstrap, reset the PHP peak counter and measured peak allocation after the command. Each peaked at 38 MiB, 8 MiB above bootstrap (target below 32 MiB). SQL confirmed 1,500 expired jobs and 4,500 active jobs in the snapshot. All eight published sitemap shards loaded over HTTP and together contained 4,500 benchmark job URLs.
   - Removed the disposable stack with `docker compose -p jobboard-s5-82da down -v`. The README covers customization, scheduled operations and service health.
