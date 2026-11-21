<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE jobs ALTER COLUMN salary_currency SET DEFAULT 'EUR'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE jobs ALTER COLUMN salary_currency SET DEFAULT 'USD'");
    }
};
