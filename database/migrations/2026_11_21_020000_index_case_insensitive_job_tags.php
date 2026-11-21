<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('CREATE INDEX jobs_tags_lower ON jobs USING gin ((lower(tags::text)::jsonb) jsonb_path_ops)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX jobs_tags_lower');
    }
};
