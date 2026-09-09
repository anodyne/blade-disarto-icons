# Blade Disarto Icons

<a href="https://github.com/anodyne/blade-disarto-icons/actions?query=workflow%3ATests"><img src="https://github.com/anodyne/blade-disarto-icons/workflows/Tests/badge.svg" alt="Tests"></a>
<a href="https://packagist.org/packages/anodyne/blade-disarto-icons"><img src="https://poser.pugx.org/anodyne/blade-disarto-icons/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/anodyne/blade-disarto-icons"><img src="https://poser.pugx.org/anodyne/blade-disarto-icons/d/total.svg" alt="Total Downloads"></a>

A package to easily make use of [Disarto Icons](https://www.figma.com/community/file/1493702468506794656/disarto-icons) in your Laravel Blade views.

For a full list of available icons see [the SVG directory](resources/svg) or preview them on the [web](https://www.figma.com/community/file/1493702468506794656/disarto-icons).

## Requirements

- PHP 8.1 or higher
- Laravel 9.0 or higher

## Installation

```bash
composer require anodyne/blade-disarto-icons
```

## Usage

Icons can be used a self-closing Blade components which will be compiled to SVG icons:

```blade
<x-disarto-alert-triangle />
```

You can also pass classes to your icon components:

```blade
<x-disarto-alert-triangle class="size-6 text-gray-500"/>
```

And even use inline styles:

```blade
<x-disarto-alert-triangle style="color: #555"/>
```

### Raw SVG Icons

If you want to use the raw SVG icons as assets, you can publish them using:

```bash
php artisan vendor:publish --tag=blade-disarto-icons --force
```

Then use them in your views like:

```blade
<img src="{{ asset('vendor/blade-disarto-icons/alert-triangle.svg') }}" width="24" height="24"/>
```

### Blade Icons

Blade Disarto Icons uses Blade Icons under the hood. Please refer to [the Blade Icons readme](https://github.com/blade-ui-kit/blade-icons) for additional functionality.

### Enum

Blade Disarto Icons includes an enum that maps every icon to an enum case. This allows for easily referencing specific icons from PHP. This is also helpful when using Disarto Icons with a system like [Filament](https://filamentphp.com/) for referencing icons.

```php
use Anodyne\DisartoIcons\DisartoIcon;

svg(DisartoIcon::AlertTriangle->value);
svg(DisartoIcon::AlertTriangleSolid->value);
```

## Testing

Install PHP and JavaScript development dependencies before running the Pest suite:

```bash
composer install
bun install
composer test
```

Tests cover the compiler in temporary directories, reproducibility of all generated
assets, enum coverage, SVG validity, Blade rendering, and asset publishing.

## Changelog

Check out the [CHANGELOG](CHANGELOG.md) in this repository for all the recent changes.

## Maintainers

Blade Disarto Icons was developed by [Anodyne Productions](https://anodyne-productions.com).

## License

Blade Disarto Icons is open-sourced software licensed under [the MIT license](LICENSE.md).
