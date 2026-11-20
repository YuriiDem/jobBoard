<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TaxonomyResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class InstallJobboard extends Command
{
    protected $signature = 'jobboard:install {--demo : Seed demonstration records}';

    protected $description = 'Configure the board, migrate PostgreSQL and initialize the owner account';

    public function handle(TaxonomyResolver $taxonomy): int
    {
        $interactive = $this->input->isInteractive();
        $settings = [];
        foreach (['niche_name' => ['BOARD_NICHE', 'Niche name'], 'site_name' => ['BOARD_NAME', 'Site name'], 'default_currency' => ['BOARD_CURRENCY', 'Currency']] as $key => [$variable,$label]) {
            $default = config("jobboard.$key");
            $value = $interactive ? trim($this->ask($label, $default)) : $default;
            Validator::make([$key => $value], [$key => $key === 'default_currency' ? ['required', 'regex:/^[A-Z]{3}$/'] : ['required', 'string', 'max:120', 'not_regex:/[\r\n\x00]/']])->validate();
            config(["jobboard.$key" => $value]);
            $settings[$variable] = $value;
        }

        if (DB::connection()->getDriverName() !== 'pgsql' || (int) DB::scalar("SELECT current_setting('server_version_num')") < 160000) {
            $this->error('Configure PostgreSQL 16 or later before installing.');

            return self::FAILURE;
        }

        $path = app()->environmentFilePath();
        $contents = File::exists($path) ? File::get($path) : File::get(base_path('.env.example'));
        if (! config('app.key')) {
            $settings['APP_KEY'] = 'base64:'.base64_encode(random_bytes(32));
        }
        if ($interactive || ! File::exists($path) || isset($settings['APP_KEY'])) {
            foreach ($settings as $key => $value) {
                $line = $key.'="'.addcslashes($value, '\\\\"$').'"';
                $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
                $contents = preg_match($pattern, $contents) ? preg_replace_callback($pattern, fn () => $line, $contents) : rtrim($contents).PHP_EOL.$line.PHP_EOL;
            }
            File::replace($path, $contents, 0600);
        }

        if ($this->call('migrate', ['--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }
        $taxonomy->sync();
        $seed = $this->option('demo') || config('jobboard.demo') || ($interactive && $this->confirm('Seed example jobs and companies?'));
        if ($seed && $this->call('db:seed', ['--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        if (! User::where('is_admin', true)->exists()) {
            $email = config('jobboard.admin_email') ?: ($interactive ? $this->ask('First admin email') : null);
            if ($email) {
                $file = config('jobboard.admin_password_file');
                $password = $file ? rtrim(File::get($file), "\r\n") : ($interactive ? $this->secret('Admin password (at least 12 characters)') : null);
                Validator::make(compact('email', 'password'), ['email' => 'required|email|max:255|unique:users', 'password' => 'required|string|min:12|max:72'])->validate();
                $user = new User(['name' => 'Administrator', 'email' => $email, 'password' => $password]);
                $user->is_admin = true;
                $user->save();
            } else {
                $this->info('Run the interactive installer to create the first admin.');
            }
        }
        $this->info('Job board installation complete.');

        return self::SUCCESS;
    }
}
