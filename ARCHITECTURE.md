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

Worktree: ../kelp-core-starter, branch jobboard/core-starter. Original kelp checkout contains the previous Laravel 13 React starter. Continue in the new worktree. This is a partial implementation.

1. **Foundation (in progress).** Laravel 12.73.0 and Filament 3.3.57 resolved with the Spatie packages and google/auth. Generated standard panel/resources. Initial PostgreSQL migration, models, jobboard config, taxonomy resolver, SEO metadata and route declarations exist. Frontend dependencies installed in staging. Dependencies reproducible from lockfiles. Route controllers, admin fields and taxonomy installer sync are pending. No database or HTTP verification yet.
2. **Next session: finish foundation.** Complete Docker and installer, finalize .env.example, test clean and repeat installs, verify migrations on PostgreSQL, admin access and configurable demo data. Use a dedicated Compose project/test database. Do not migrate or reset a pre-existing host database. Remove skeleton defaults that conflict with PostgreSQL/Redis. Verify composer install and npm ci on the durable worktree.
3. **Public directory and SEO.** Complete controllers, Blade, filters, hub discovery, canonicalization, JobPosting and metrics. Add feature and query-count tests as each route lands. Verify short URLs, page order and cross-source slugs.
4. **Background work and admin.** Expiration, market snapshots, cache invalidation, indexing notifications, sitemap publication and Filament CRUD. Verify safe retries, admin authorization, eager loading and sitemap index eligibility.
5. **Release verification.** One-command clean boot, test suite, Docker health, memory measurements, customization instructions and final architecture update. Record exact commands, results and remaining work at each session boundary.

