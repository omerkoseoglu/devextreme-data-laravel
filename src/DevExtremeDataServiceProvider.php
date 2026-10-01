<?php

declare(strict_types=1);

namespace DevExtreme\Data\Laravel;

use DevExtreme\Data\LoadOptions;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

final class DevExtremeDataServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/devextreme-data.php', 'devextreme-data');

        $this->app->singleton(DevExtremeLoader::class, static function (Application $app): DevExtremeLoader {
            $config = $app->make('config');
            $maxTake = $config->get('devextreme-data.max_take');

            return new DevExtremeLoader(
                static fn (): Request => $app->make('request'), // resolved per call: Octane/test safe
                (bool) $config->get('devextreme-data.normalize_dates', true),
                $maxTake === null ? null : (int) $maxTake,
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/devextreme-data.php' => $this->app->configPath('devextreme-data.php'),
            ], 'devextreme-data-config');
        }

        // $request->devExtremeOptions()
        Request::macro('devExtremeOptions', function (): LoadOptions {
            /** @var Request $this */
            return app(DevExtremeLoader::class)->options($this);
        });
    }
}
