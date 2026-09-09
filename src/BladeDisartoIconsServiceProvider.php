<?php

declare(strict_types=1);

namespace Anodyne\DisartoIcons;

use BladeUI\Icons\Factory;
use Illuminate\Support\ServiceProvider;

final class BladeDisartoIconsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->callAfterResolving(Factory::class, function (Factory $factory) {
            $factory->add('disarto', [
                'path' => __DIR__.'/../resources/svg',
                'prefix' => 'disarto',
                'class' => 'disarto-icon',
            ]);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/svg' => public_path('vendor/blade-disarto-icons'),
            ], 'blade-disarto-icons');
        }
    }
}
