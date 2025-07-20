<?php

declare(strict_types=1);

namespace LaravelS3Server;

use Illuminate\Support\ServiceProvider;
use LaravelS3Server\Contracts\AuthenticationDriver;
use LaravelS3Server\Http\Middleware\S3SignatureMiddleware;

/**
 * S3 service provider.
 *
 * This service provider is used to register the S3 server routes and views
 *
 * @author Marcel Menk <marcel.menk@ipvx.io>
 */
class S3ServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/s3.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 's3server');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes([
            __DIR__ . '/../config/s3server.php' => config_path('s3server.php'),
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 's3server-config');

        // Register the s3.auth middleware
        $this->app['router']->aliasMiddleware('s3.auth', S3SignatureMiddleware::class);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/s3server.php', 's3server');
        $this->mergeConfigFrom(__DIR__ . '/../config/s3server.php', 's3');

        // Bind the authentication provider interface to the configured driver
        $this->app->bind(AuthenticationDriver::class, function ($app) {
            $authDriverClass = config('s3server.auth_driver');

            return $app->make($authDriverClass);
        });
    }
}
