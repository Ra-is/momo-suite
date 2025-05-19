<?php

namespace Rais\MomoSuite\Console;

use Illuminate\Console\Command;
use Rais\MomoSuite\Models\User;
use Illuminate\Support\Facades\Hash;

class InstallCommand extends Command
{
    protected $signature = 'momo-suite:install 
        {--all : Publish all assets}
        {--views : Publish the views for customization}
        {--routes : Publish the routes for customization}
        {--migrations : Publish the migrations for customization}
        {--force : Force publish all files}';

    protected $description = 'Install the Momo Suite package';

    public function handle()
    {
        $this->info('Installing Momo Suite...');

        if ($this->option('all')) {
            // Publish everything at once
            $this->call('vendor:publish', [
                '--provider' => 'Rais\MomoSuite\MomoSuiteServiceProvider',
                '--tag' => 'momo-suite-all',
                '--force' => $this->option('force')
            ]);
        } else {
            // Always publish config, assets and migrations
            $this->publishConfig();
            $this->publishAssets();
            $this->publishMigrations();

            // Publish other assets based on options
            if ($this->option('views')) {
                $this->publishViews();
            }

            if ($this->option('routes')) {
                $this->publishRoutes();
            }
        }

        // Run migrations
        $this->info('Migrations have been published. Please run `php artisan migrate` to create the necessary tables.');

        // Show available customization options if not all published
        if (!$this->option('all')) {
            $this->showCustomizationOptions();
        }
    }

    protected function publishConfig()
    {
        $this->info('Publishing configuration...');
        if (file_exists(config_path('momo-suite.php')) && !$this->option('force')) {
            $this->warn('Config file already exists. Use --force to overwrite.');
            return;
        }

        $this->call('vendor:publish', [
            '--provider' => 'Rais\MomoSuite\MomoSuiteServiceProvider',
            '--tag' => 'momo-suite-config',
            '--force' => $this->option('force'),
        ]);

        $this->info('Configuration file published to: config/momo-suite.php');
        $this->info('You can now customize the package settings in this file.');
    }

    protected function publishAssets()
    {
        $this->info('Publishing assets...');
        $this->call('vendor:publish', [
            '--provider' => 'Rais\MomoSuite\MomoSuiteServiceProvider',
            '--tag' => 'momo-suite-assets',
            '--force' => $this->option('force'),
        ]);
    }

    protected function publishViews()
    {
        $this->info('Publishing views...');
        $this->warn('Note: Publishing views will make it harder to receive view updates from the package.');
        $this->call('vendor:publish', [
            '--provider' => 'Rais\MomoSuite\MomoSuiteServiceProvider',
            '--tag' => 'momo-suite-views',
            '--force' => $this->option('force'),
        ]);
    }

    protected function publishRoutes()
    {
        $this->info('Publishing routes...');
        $this->call('vendor:publish', [
            '--provider' => 'Rais\MomoSuite\MomoSuiteServiceProvider',
            '--tag' => 'momo-suite-routes',
            '--force' => $this->option('force'),
        ]);
    }

    protected function publishMigrations()
    {
        $this->info('Publishing migrations...');
        $this->call('vendor:publish', [
            '--provider' => 'Rais\MomoSuite\MomoSuiteServiceProvider',
            '--tag' => 'momo-suite-migrations',
            '--force' => $this->option('force'),
        ]);
    }

    protected function showCustomizationOptions()
    {
        $this->info('');
        $this->info('Available customization options:');
        $this->info('');
        $this->info('Publish everything:');
        $this->info('  php artisan momo-suite:install --all');
        $this->info('');
        $this->info('Or publish specific assets:');
        $this->info('  php artisan momo-suite:install --views     # Customize views');
        $this->info('  php artisan momo-suite:install --routes    # Customize routes');
        $this->info('  php artisan momo-suite:install --migrations # Customize migrations');
        $this->info('');
        $this->info('Add --force to overwrite existing files');
    }
}
