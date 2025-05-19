<?php

namespace Rais\MomoSuite;

use Illuminate\Support\ServiceProvider;
use Rais\MomoSuite\Console\InstallCommand;
use Rais\MomoSuite\Console\CreateAdminCommand;
use Rais\MomoSuite\Providers\KorbaProvider;
use Rais\MomoSuite\Providers\ItcProvider;
use Rais\MomoSuite\Providers\HubtelProvider;
use Rais\MomoSuite\Providers\PaystackProvider;
use Rais\MomoSuite\Services\MomoService;
use Illuminate\Support\Facades\Blade;
use Rais\MomoSuite\Http\Middleware\Authenticate;
use Rais\MomoSuite\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\Auth;
use Rais\MomoSuite\Models\User;
use Rais\MomoSuite\View\Components\AppLayout;

class MomoSuiteServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/momo-suite.php',
            'momo-suite'
        );

        $this->app->singleton(MomoService::class, function ($app) {
            $momoService = new MomoService(config('momo-suite'));

            // Register all providers
            $momoService->registerProvider('korba', new KorbaProvider(config('momo-suite.providers.korba')));
            $momoService->registerProvider('itc', new ItcProvider(config('momo-suite.providers.itc')));
            $momoService->registerProvider('hubtel', new HubtelProvider(config('momo-suite.providers.hubtel')));
            $momoService->registerProvider('paystack', new PaystackProvider(config('momo-suite.providers.paystack')));

            return $momoService;
        });

        // Register the service with 'momo-suite' key
        $this->app->singleton('momo-suite', function ($app) {
            return $app->make(MomoService::class);
        });
    }

    public function boot()
    {
        // Always load webhook/API routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/webhook.php');

        // Only load dashboard UI if enabled in config
        if (config('momo-suite.load_dashboard', false)) {
            // Load published views if they exist, otherwise use package views
            $publishedViews = base_path('resources/views/vendor/momo-suite');
            if (is_dir($publishedViews)) {
                $this->loadViewsFrom($publishedViews, 'momo-suite');
            } else {
                $this->loadViewsFrom(__DIR__ . '/../resources/views', 'momo-suite');
            }

            // Load published dashboard routes if they exist, otherwise use package dashboard routes
            $dashboardRoutes = base_path('routes/momo-suite.php');
            if (file_exists($dashboardRoutes)) {
                $this->loadRoutesFrom($dashboardRoutes);
            } else {
                $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
            }

            // Register Blade components for dashboard
            Blade::component('momo-suite::layouts.app', 'app-layout');
            Blade::component('momo-suite::components.dropdown', 'dropdown');
            Blade::component('momo-suite::components.dropdown-link', 'dropdown-link');
        }

        // Load published migrations if they exist, otherwise use package migrations
        $publishedMigrations = database_path('migrations/momo-suite');
        if (is_dir($publishedMigrations)) {
            $this->loadMigrationsFrom($publishedMigrations);
        } else {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }

        // Configure authentication
        config([
            'auth.guards.momo' => [
                'driver' => 'session',
                'provider' => 'momo',
            ],
            'auth.providers.momo' => [
                'driver' => 'eloquent',
                'model' => User::class,
            ],
        ]);

        // Register middleware
        $this->app['router']->aliasMiddleware('auth.momo', Authenticate::class);
        $this->app['router']->aliasMiddleware('guest.momo', RedirectIfAuthenticated::class);
        $this->app['router']->pushMiddlewareToGroup('web', ShareErrorsFromSession::class);

        if ($this->app->runningInConsole()) {
            // Only config and migrations by default
            $this->publishes([
                __DIR__ . '/../config/momo-suite.php' => config_path('momo-suite.php'),
                __DIR__ . '/../database/migrations' => database_path('migrations/momo-suite'),
            ]);

            // Tagged publishes for other assets
            $this->publishes([
                __DIR__ . '/../routes/web.php' => base_path('routes/momo-suite.php'),
            ], 'momo-suite-routes');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/momo-suite'),
            ], 'momo-suite-views');

            $this->publishes([
                __DIR__ . '/../public' => public_path('vendor/momo-suite'),
            ], 'momo-suite-assets');

            // 'all' tag for everything
            $this->publishes([
                __DIR__ . '/../config/momo-suite.php' => config_path('momo-suite.php'),
                __DIR__ . '/../database/migrations' => database_path('migrations/momo-suite'),
                __DIR__ . '/../routes/web.php' => base_path('routes/momo-suite.php'),
                __DIR__ . '/../resources/views' => resource_path('views/vendor/momo-suite'),
                __DIR__ . '/../public' => public_path('vendor/momo-suite'),
            ], 'momo-suite-all');
        }

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                CreateAdminCommand::class,
            ]);
        }

        $this->app['router']->aliasMiddleware('admin', \Rais\MomoSuite\Http\Middleware\AdminMiddleware::class);
    }
}
