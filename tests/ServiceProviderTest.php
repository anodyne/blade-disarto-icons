<?php

declare(strict_types=1);

use Anodyne\DisartoIcons\BladeDisartoIconsServiceProvider;
use Anodyne\DisartoIcons\DisartoIcon;
use BladeUI\Icons\Exceptions\SvgNotFound;
use BladeUI\Icons\Factory;
use BladeUI\Icons\IconsManifest;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Tests\TestCase;

uses(TestCase::class);

it('registers the icon path, prefix and default class', function () {
    $set = $this->app->make(Factory::class)->all()['disarto'];

    expect(realpath($set['paths'][0]))->toBe(realpath(__DIR__.'/../resources/svg'));
    expect($set['prefix'])->toBe('disarto');
    expect($set['class'])->toBe('disarto-icon');
});

it('registers icons regardless of when the factory is resolved', function (bool $resolved) {
    $app = new Container;
    $files = new Filesystem;
    $factory = new Factory($files, new IconsManifest($files, sys_get_temp_dir().'/unused-disarto-manifest'));
    $app->singleton(Factory::class, fn () => $factory);
    if ($resolved) {
        $app->make(Factory::class);
    }
    (new BladeDisartoIconsServiceProvider($app))->register();

    expect($app->make(Factory::class)->svg('disarto-heart-solid')->toHtml())->toContain('<svg', 'disarto-icon');
})->with(['already resolved' => true, 'resolved later' => false]);

it('does not register publish paths outside console requests', function () {
    $app = Mockery::mock(Application::class);
    $app->shouldReceive('runningInConsole')->once()->andReturn(false);
    $before = ServiceProvider::pathsToPublish(BladeDisartoIconsServiceProvider::class);

    (new BladeDisartoIconsServiceProvider($app))->boot();

    expect(ServiceProvider::pathsToPublish(BladeDisartoIconsServiceProvider::class))->toBe($before);
});

it('renders every enum through the Blade Icons factory', function () {
    $factory = $this->app->make(Factory::class);

    foreach (DisartoIcon::cases() as $icon) {
        $document = new DOMDocument;
        expect($document->loadXML($factory->svg($icon->value)->toHtml()))->toBeTrue();
        expect($document->documentElement->localName)->toBe('svg');
        expect($document->documentElement->getAttribute('class'))->toBe('disarto-icon');
    }
});

it('renders regular and solid Blade components with custom attributes', function (string $name) {
    $html = Blade::render('<x-disarto-'.$name.' class="size-6 text-red-500" aria-label="Example" style="color: red" />');
    $document = new DOMDocument;
    expect($document->loadXML(trim($html)))->toBeTrue();
    $svg = $document->documentElement;

    expect($svg->getAttribute('class'))->toContain('disarto-icon', 'size-6', 'text-red-500');
    expect($svg->getAttribute('aria-label'))->toBe('Example');
    expect($svg->getAttribute('style'))->toBe('color: red');
    expect($svg->getAttribute('viewBox'))->toBe('0 0 24 24');
})->with(['heart', 'heart-solid']);

it('renders icons using the documented helper and enum', function () {
    expect(svg(DisartoIcon::Alarm->value)->toHtml())->toContain('<svg', 'disarto-icon');
});

it('reports an unknown icon', function () {
    $this->app->make(Factory::class)->svg('disarto-does-not-exist');
})->throws(SvgNotFound::class);

it('publishes the complete SVG set to the documented public directory', function () {
    $destination = public_path('vendor/blade-disarto-icons');
    $paths = ServiceProvider::pathsToPublish(BladeDisartoIconsServiceProvider::class, 'blade-disarto-icons');

    expect($paths)->toHaveCount(1);
    expect(realpath(array_key_first($paths)))->toBe(realpath(__DIR__.'/../resources/svg'));
    expect(array_values($paths))->toBe([$destination]);

    try {
        mkdir($destination, 0777, true);
        file_put_contents($destination.'/alarm.svg', 'customized published icon');
        $this->artisan('vendor:publish', ['--tag' => 'blade-disarto-icons'])->assertExitCode(0);
        expect(file_get_contents($destination.'/alarm.svg'))->toBe('customized published icon');
        $this->artisan('vendor:publish', ['--tag' => 'blade-disarto-icons', '--force' => true])->assertExitCode(0);
        $sourceFiles = glob(__DIR__.'/../resources/svg/*.svg');
        expect(glob($destination.'/*.svg'))->toHaveCount(count($sourceFiles));
        foreach ($sourceFiles as $source) {
            expect(hash_file('sha256', $destination.'/'.basename($source)))->toBe(hash_file('sha256', $source));
        }
    } finally {
        $this->app['files']->deleteDirectory($destination);
    }
    expect(is_dir($destination))->toBeFalse();
});
