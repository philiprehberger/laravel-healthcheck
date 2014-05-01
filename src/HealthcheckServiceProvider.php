<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck;

use Illuminate\Support\ServiceProvider;
use PhilipRehberger\Healthcheck\Contracts\HealthCheck;
use PhilipRehberger\Healthcheck\Http\HealthController;

class HealthcheckServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/healthcheck.php',
            'healthcheck',
        );

        $this->app->singleton(HealthService::class, function (): HealthService {
            /** @var array<string, mixed> $config */
            $config = config('healthcheck', []);

            $service = new HealthService(
                timeout: (int) ($config['timeout'] ?? 5),
                cacheConfig: (array) ($config['cache'] ?? []),
            );

            /** @var array<int, class-string<HealthCheck>> $checks */
            $checks = $config['checks'] ?? [];

            foreach ($checks as $checkClass) {
                if (is_string($checkClass) && class_exists($checkClass)) {
                    /** @var HealthCheck $check */
                    $check = $this->app->make($checkClass);
                    $service->register($check);
                }
            }

            return $service;
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/healthcheck.php' => config_path('healthcheck.php'),
            ], 'healthcheck-config');
        }

        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        /** @var array<string, mixed> $config */
        $config = config('healthcheck', []);

        $prefix = (string) ($config['route_prefix'] ?? 'health');

        /** @var array<int, string> $middleware */
        $middleware = (array) ($config['middleware'] ?? []);

        $router = $this->app['router'];

        $router->group(
            ['prefix' => $prefix, 'middleware' => $middleware],
            function () use ($router): void {
                $router->get('/', HealthController::class)
                    ->name('healthcheck.index');

                $router->get('/live', [HealthController::class, 'live'])
                    ->name('healthcheck.live');

                $router->get('/ready', [HealthController::class, 'ready'])
                    ->name('healthcheck.ready');
            },
        );
    }
}
