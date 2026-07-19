<?php

declare(strict_types=1);

namespace Glytos\Laravel;

use Glytos\Client;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Registers a configured {@see Client} in the Laravel container (auto-discovered).
 *
 * Configure it with `GLYTOS_API_KEY` (and optionally `GLYTOS_BASE_URL`,
 * `GLYTOS_ENVIRONMENT`) in your `.env`, then inject {@see Client} or use the
 * {@see Glytos} facade. Publish the config with
 * `php artisan vendor:publish --tag=glytos-config`.
 */
final class GlytosServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/glytos.php', 'glytos');

        $this->app->singleton(Client::class, static function (Application $app): Client {
            /** @var array{api_key?: string, base_url?: string, environment?: string|null} $config */
            $config = $app['config']['glytos'] ?? [];

            return new Client(
                (string) ($config['api_key'] ?? ''),
                (string) ($config['base_url'] ?? Client::DEFAULT_BASE_URL),
                $config['environment'] ?? null,
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/glytos.php' => $this->app->configPath('glytos.php'),
            ], 'glytos-config');
        }
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [Client::class];
    }
}
