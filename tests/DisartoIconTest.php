<?php

declare(strict_types=1);

use Anodyne\DisartoIcons\DisartoIcon;

it('covers every upstream variant with matching PascalCase names and no extra icons', function () {
    $expected = [];

    foreach (['regular', 'solid'] as $variant) {
        $files = glob(__DIR__.'/../node_modules/disarto-icons-static/icons/'.$variant.'/*.svg');
        expect($files)->not->toBeEmpty();

        foreach ($files as $file) {
            $name = basename($file, '.svg').($variant === 'solid' ? '-solid' : '');
            $case = str_replace(' ', '', ucwords(str_replace('-', ' ', $name)));
            $expected['disarto-'.$name] = $case;
        }
    }

    $actual = [];
    foreach (DisartoIcon::cases() as $icon) {
        $actual[$icon->value] = $icon->name;
    }

    ksort($expected);
    expect($actual)->toBe($expected);
});

it('provides exactly one enum case for every generated SVG', function () {
    $files = array_map('basename', glob(__DIR__.'/../resources/svg/*.svg'));
    $values = array_map(fn (DisartoIcon $icon) => $icon->value, DisartoIcon::cases());
    $expected = array_map(fn (string $file) => 'disarto-'.basename($file, '.svg'), $files);

    sort($values);
    sort($expected);

    expect($files)->not->toBeEmpty();
    expect($values)->toBe($expected);
});

it('ships valid scalable SVGs for every enum value', function () {
    foreach (DisartoIcon::cases() as $icon) {
        $path = __DIR__.'/../resources/svg/'.substr($icon->value, strlen('disarto-')).'.svg';
        $document = new DOMDocument;

        expect($document->load($path))->toBeTrue();
        expect($document->documentElement->localName)->toBe('svg');
        expect($document->documentElement->namespaceURI)->toBe('http://www.w3.org/2000/svg');
        expect($document->documentElement->getAttribute('viewBox'))->not->toBeEmpty();
    }
});
