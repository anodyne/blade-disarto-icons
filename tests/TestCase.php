<?php

declare(strict_types=1);

namespace Tests;

use Anodyne\DisartoIcons\BladeDisartoIconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [BladeIconsServiceProvider::class, BladeDisartoIconsServiceProvider::class];
    }
}
