<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up(): void {
 DB::unprepared(<<<'SQL'
ALTER TABLE users ADD COLUMN is_admin boolean NOT NULL DEFAULT false;
CREATE TABLE companies (
 id bigserial PRIMARY KEY, name varchar NOT NULL, slug varchar UNIQUE NOT NULL, tagline varchar, website_url text, logo_url text, description text,
 founded_year smallint, headquarters varchar, team_size varchar CHECK(team_size IN ('1_10','11_50','51_200','201_500','501_plus')),
 funding_stage varchar CHECK(funding_stage IN ('bootstrapped','pre_seed','seed','series_a','series_b','series_c_plus','public','acquired')),
 total_funding_amount varchar, investors jsonb, markets jsonb, social_links jsonb, ats_provider varchar CHECK(ats_provider IN ('greenhouse','lever','ashby','workable','custom')),
 ats_slug varchar,last_synced_at timestamptz,sync_error text,is_verified boolean DEFAULT false NOT NULL,is_featured boolean DEFAULT false NOT NULL,created_at timestamptz,updated_at timestamptz);
CREATE INDEX companies_markets ON companies USING gin(markets);
CREATE TABLE departments(id bigserial PRIMARY KEY,name varchar NOT NULL,slug varchar UNIQUE NOT NULL,description text,sort_order integer NOT NULL DEFAULT 0,created_at timestamptz,updated_at timestamptz);
CREATE TABLE roles(id bigserial PRIMARY KEY,department_id bigint NOT NULL REFERENCES departments ON DELETE CASCADE,name varchar NOT NULL,slug varchar UNIQUE NOT NULL,description text,created_at timestamptz,updated_at timestamptz);
CREATE INDEX roles_department_slug ON roles(department_id,slug);
CREATE TABLE locations(id bigserial PRIMARY KEY,name varchar NOT NULL,slug varchar UNIQUE NOT NULL,type varchar NOT NULL CHECK(type IN ('region','country','city','remote')),country_code varchar,country_name varchar,parent_id bigint REFERENCES locations ON DELETE SET NULL,is_popular boolean NOT NULL DEFAULT false,created_at timestamptz,updated_at timestamptz);
CREATE TABLE jobs(id bigserial PRIMARY KEY,company_id bigint NOT NULL REFERENCES companies ON DELETE CASCADE,department_id bigint REFERENCES departments ON DELETE SET NULL,role_id bigint REFERENCES roles ON DELETE SET NULL,primary_location_id bigint REFERENCES locations ON DELETE SET NULL,
 title varchar NOT NULL,slug varchar UNIQUE NOT NULL,description_html text,description_plain text,raw_location varchar,raw_department varchar,
 employment_type varchar CHECK(employment_type IN ('full_time','part_time','contract','internship','working_student','co_founder')),
 workplace_type varchar CHECK(workplace_type IN ('remote','hybrid','onsite')),seniority_level varchar CHECK(seniority_level IN ('junior','mid','senior','staff_principal','lead_manager','executive')),
 salary_min integer,salary_max integer,salary_currency varchar DEFAULT 'USD',salary_period varchar CHECK(salary_period IN ('year','month','hour')),equity_min decimal(5,3),equity_max decimal(5,3),
 tags jsonb NOT NULL DEFAULT '[]',apply_url text NOT NULL CHECK(apply_url ~* '^https?://[^[:space:]]+$'), apply_email varchar,
 source varchar NOT NULL DEFAULT 'direct' CHECK(source IN ('direct','api_greenhouse','api_lever','api_ashby','scraper','csv')),external_id varchar,status varchar NOT NULL DEFAULT 'draft' CHECK(status IN ('draft','pending_payment','published','expired')),
 is_featured boolean NOT NULL DEFAULT false,needs_enrichment boolean NOT NULL DEFAULT false,published_at timestamptz,last_seen_at timestamptz,expires_at timestamptz,created_at timestamptz,updated_at timestamptz,
 search_vector tsvector GENERATED ALWAYS AS (to_tsvector('simple',title || ' ' || coalesce(description_plain,''))) STORED);
CREATE INDEX jobs_search ON jobs USING gin(search_vector);
CREATE INDEX jobs_tags ON jobs USING gin(tags);
CREATE INDEX jobs_featured ON jobs(status,is_featured,published_at,id);
CREATE INDEX jobs_roles ON jobs(status,role_id,published_at);
CREATE INDEX jobs_departments ON jobs(status,department_id,published_at);
CREATE INDEX jobs_workplace ON jobs(status,workplace_type);
CREATE INDEX jobs_company ON jobs(company_id);
CREATE INDEX jobs_location ON jobs(primary_location_id);
CREATE INDEX jobs_expiration ON jobs(expires_at) WHERE status='published';
CREATE UNIQUE INDEX jobs_external ON jobs(source,external_id) WHERE external_id IS NOT NULL;
CREATE TABLE job_location(job_id bigint REFERENCES jobs ON DELETE CASCADE,location_id bigint REFERENCES locations ON DELETE CASCADE,PRIMARY KEY(job_id,location_id));
CREATE INDEX location_jobs ON job_location(location_id,job_id);
CREATE TABLE job_metrics(id bigserial PRIMARY KEY,job_id bigint NOT NULL REFERENCES jobs ON DELETE CASCADE,date date NOT NULL,views_count integer NOT NULL DEFAULT 0,apply_clicks_count integer NOT NULL DEFAULT 0,UNIQUE(job_id,date));
CREATE TABLE daily_stats(id bigserial PRIMARY KEY,date date UNIQUE NOT NULL,total_active_jobs integer NOT NULL,remote_jobs_count integer NOT NULL,median_salary_usd integer,top_roles jsonb NOT NULL,top_tags jsonb NOT NULL);
CREATE TABLE public_slugs(slug varchar PRIMARY KEY CHECK(slug ~ '^[a-z0-9]+(-[a-z0-9]+)*$' AND slug <> 'apply'),kind varchar NOT NULL,owner_id bigint,value varchar);
CREATE FUNCTION register_slug() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
 IF TG_OP IN ('DELETE','UPDATE') THEN DELETE FROM public_slugs WHERE slug=OLD.slug AND kind=TG_TABLE_NAME; END IF;
 IF TG_OP <> 'DELETE' THEN INSERT INTO public_slugs(slug,kind,owner_id) VALUES(NEW.slug,TG_TABLE_NAME,NEW.id); END IF;
 RETURN NULL;
END $$;
SQL);
 foreach (['departments','roles','locations','jobs'] as $table) {
 DB::statement("CREATE TRIGGER register_slug AFTER INSERT OR UPDATE OF slug OR DELETE ON {$table} FOR EACH ROW EXECUTE FUNCTION register_slug()");
 }
 }
 public function down(): void {
 DB::unprepared('DROP TABLE job_location,job_metrics,daily_stats,jobs,roles,locations,departments,companies,public_slugs; DROP FUNCTION register_slug(); ALTER TABLE users DROP COLUMN is_admin');
 }
};
