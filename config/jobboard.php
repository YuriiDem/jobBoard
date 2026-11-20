<?php

return [
    'demo' => env('BOARD_DEMO', false), 'admin_email' => env('BOARD_ADMIN_EMAIL'), 'admin_password_file' => env('BOARD_ADMIN_PASSWORD_FILE'),
    'niche_name' => env('BOARD_NICHE', 'Startup'), 'site_name' => env('BOARD_NAME', 'Job Board'),
    'default_currency' => env('BOARD_CURRENCY', 'USD'), 'jobs_per_page' => (int) env('JOBS_PER_PAGE', 25), 'default_expiration_days' => (int) env('JOB_EXPIRATION_DAYS', 30),
    'featured_tags' => array_values(array_filter(array_map('trim', explode(',', env('FEATURED_TAGS', ''))))),
    'markets' => array_values(array_filter(array_map('trim', explode(',', env('BOARD_MARKETS', ''))))),
    'approved_pairs' => array_filter(explode(',', env('APPROVED_PAIRS', ''))), 'min_jobs_to_index' => max(1, (int) env('MIN_JOBS_TO_INDEX', 1)),
    'employment' => ['internships' => 'internship', 'working-student' => 'working_student', 'full-time' => 'full_time', 'part-time' => 'part_time', 'contracts' => 'contract', 'co-founder' => 'co_founder'],
    'filters' => ['stage', 'min_salary', 'workplace', 'tag', 'q'],
    'seo' => ['title' => env('SEO_TITLE', ':category Jobs :country | :site_name'), 'description' => env('SEO_DESCRIPTION', 'Explore :niche opportunities :country on :site_name.'), 'llms_txt_intro' => env('LLMS_INTRO', 'Explore current vacancies and hiring companies.'), 'credentials' => env('GOOGLE_INDEXING_CREDENTIALS')],
    'theme' => ['primary_color' => env('PRIMARY_COLOR', '#164e63'), 'logo_path' => env('LOGO_PATH'), 'dark_mode_default' => env('DARK_MODE_DEFAULT', false)],
    'stripe' => ['standard_price_cents' => (int) env('STANDARD_PRICE_CENTS',0), 'featured_price_cents' => (int) env('FEATURED_PRICE_CENTS',0)],
];
