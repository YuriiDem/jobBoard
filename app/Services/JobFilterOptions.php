<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class JobFilterOptions
{
    public const SALARY_THRESHOLDS = [30000, 50000, 75000, 100000, 125000, 150000, 200000];

    public static function forListing(Builder $listing, string $path): array
    {
        $currency = config('jobboard.default_currency');
        return Cache::remember('job-facets:'.$currency.':'.sha1($path), now()->addMinutes(5), function () use ($listing, $currency) {
            $base = $listing->toBase();
            $thresholds = implode(',', self::SALARY_THRESHOLDS);
            $rows = DB::select(<<<SQL
                WITH openings AS ({$base->toSql()}),
                places AS (
                    SELECT id AS job_id, primary_location_id AS location_id FROM openings
                    UNION
                    SELECT jl.job_id, jl.location_id FROM job_location jl JOIN openings j ON j.id = jl.job_id
                ), options AS (
                    SELECT j.id, f.kind, f.value, f.label
                    FROM openings j
                    LEFT JOIN roles r ON r.id = j.role_id
                    JOIN companies c ON c.id = j.company_id
                    CROSS JOIN LATERAL (VALUES
                        ('role', r.slug, r.name), ('stage', c.funding_stage, NULL),
                        ('workplace', j.workplace_type, NULL), ('employment', j.employment_type, NULL),
                        ('level', j.seniority_level, NULL)
                    ) f(kind, value, label)
                    UNION ALL
                    SELECT p.job_id, 'location', l.slug, l.name FROM places p JOIN locations l ON l.id = p.location_id
                    UNION ALL
                    SELECT j.id, 'tag', t.name, t.name FROM openings j CROSS JOIN LATERAL jsonb_array_elements_text(j.tags) t(name)
                    UNION ALL
                    SELECT j.id, 'min_salary', threshold::text, NULL FROM openings j
                    CROSS JOIN unnest(ARRAY[$thresholds]) threshold
                    WHERE j.salary_currency = ? AND j.salary_period = 'year' AND j.salary_min >= threshold
                )
                SELECT kind, value, label, count(DISTINCT id) AS total FROM options
                WHERE value IS NOT NULL GROUP BY kind, value, label ORDER BY total DESC, value
                SQL, [...$base->getBindings(), $currency]);

            $options = [];
            foreach ($rows as $row) {
                $options[$row->kind][$row->value] = ['label' => $row->label, 'count' => $row->total];
            }
            return $options;
        });
    }
}
