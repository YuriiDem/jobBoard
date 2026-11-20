<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MarketSnapshot extends Command
{
    protected $signature = 'jobboard:snapshot';

    protected $description = 'Record the current active market for today';

    public function handle(): int
    {
        DB::statement(<<<'SQL'
            WITH active AS MATERIALIZED (
                SELECT role_id, tags, workplace_type, salary_min, salary_period, salary_currency
                FROM jobs WHERE status='published' AND published_at<=CURRENT_TIMESTAMP
                AND (expires_at IS NULL OR expires_at>CURRENT_TIMESTAMP)
            ), roles_ranked AS (
                SELECT roles.slug, count(*) AS jobs FROM active JOIN roles ON roles.id=active.role_id
                GROUP BY roles.slug ORDER BY jobs DESC, roles.slug LIMIT 10
            ), tags_ranked AS (
                SELECT tag, count(*) AS jobs FROM active
                CROSS JOIN LATERAL (SELECT DISTINCT jsonb_array_elements_text(tags) AS tag) AS terms
                GROUP BY tag ORDER BY jobs DESC, tag LIMIT 10
            )
            INSERT INTO daily_stats(date,total_active_jobs,remote_jobs_count,median_salary_usd,top_roles,top_tags)
            SELECT ?, count(*), count(*) FILTER (WHERE workplace_type='remote'),
                round((percentile_cont(0.5) WITHIN GROUP (ORDER BY salary_min)
                    FILTER (WHERE salary_period='year' AND salary_currency='USD'))::numeric),
                coalesce((SELECT jsonb_agg(to_jsonb(r) ORDER BY jobs DESC,slug) FROM roles_ranked r),'[]'),
                coalesce((SELECT jsonb_agg(to_jsonb(t) ORDER BY jobs DESC,tag) FROM tags_ranked t),'[]')
            FROM active
            ON CONFLICT (date) DO UPDATE SET total_active_jobs=excluded.total_active_jobs,
                remote_jobs_count=excluded.remote_jobs_count, median_salary_usd=excluded.median_salary_usd,
                top_roles=excluded.top_roles, top_tags=excluded.top_tags
            SQL, [today()->toDateString()]);

        return self::SUCCESS;
    }
}
