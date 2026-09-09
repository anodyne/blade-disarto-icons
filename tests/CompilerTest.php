<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Tests\Support\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler;
});

afterEach(function () {
    $this->compiler->cleanup();
});

it('reproduces every shipped SVG and the enum from the installed upstream package', function () {
    $files = new Filesystem;
    $destination = $this->compiler->root.'/node_modules/disarto-icons-static/icons';
    $files->deleteDirectory($destination);
    $files->copyDirectory(__DIR__.'/../node_modules/disarto-icons-static/icons', $destination);

    $result = $this->compiler->run();
    expect($result->isSuccessful())->toBeTrue($result->getErrorOutput());

    $expected = [];
    foreach (glob(__DIR__.'/../resources/svg/*.svg') as $path) {
        $expected[basename($path)] = file_get_contents($path);
    }
    $expected['enum'] = file_get_contents(__DIR__.'/../src/DisartoIcon.php');

    expect($this->compiler->outputs())->toBe($expected);
});

it('compiles both variants, optimizes SVGs and generates valid namespaced PHP', function () {
    $this->compiler->icon('regular', 'arrow-circle-up');
    $this->compiler->icon('solid', 'gear-2');
    $result = $this->compiler->run();

    expect($result->isSuccessful())->toBeTrue($result->getErrorOutput());
    $outputs = $this->compiler->outputs();
    expect(array_keys($outputs))->toBe(['arrow-circle-up.svg', 'gear-2-solid.svg', 'heart-solid.svg', 'heart.svg', 'enum']);
    expect($outputs['enum'])
        ->toContain('namespace Anodyne\\DisartoIcons;', 'enum DisartoIcon: string', "case ArrowCircleUp = 'disarto-arrow-circle-up';", "case Gear2Solid = 'disarto-gear-2-solid';", "case Heart = 'disarto-heart';", "case HeartSolid = 'disarto-heart-solid';");

    foreach (array_diff_key($outputs, ['enum' => true]) as $svg) {
        expect(strlen($svg))->toBeLessThan(strlen(Compiler::SVG));
        expect($svg)->not->toContain('removable metadata');
        $document = new DOMDocument;
        expect($document->loadXML($svg))->toBeTrue();
        expect($document->documentElement->getAttribute('viewBox'))->toBe('0 0 24 24');
        expect($svg)->toContain('currentColor');
    }

    $php = new Process([PHP_BINARY, '-l', $this->compiler->root.'/src/DisartoIcon.php']);
    $php->run();
    expect($php->isSuccessful())->toBeTrue($php->getErrorOutput());
});

it('ignores non SVG files and nested directories', function () {
    $directory = $this->compiler->root.'/node_modules/disarto-icons-static/icons/regular';
    file_put_contents($directory.'/README.md', 'Not an icon');
    mkdir($directory.'/nested.svg');
    file_put_contents($directory.'/nested.svg/hidden.svg', Compiler::SVG);

    expect($this->compiler->run()->isSuccessful())->toBeTrue();
    expect(array_keys($this->compiler->outputs()))->toBe(['heart-solid.svg', 'heart.svg', 'enum']);
});

it('rebuilds deterministically and replaces changed icons while removing stale icons and enum cases', function () {
    $this->compiler->icon('regular', 'old');
    expect($this->compiler->run()->isSuccessful())->toBeTrue();
    $before = $this->compiler->outputs();
    expect($this->compiler->run()->isSuccessful())->toBeTrue();
    expect($this->compiler->outputs())->toBe($before);

    unlink($this->compiler->root.'/node_modules/disarto-icons-static/icons/regular/old.svg');
    $this->compiler->icon('regular', 'heart', str_replace('24', '16', Compiler::SVG));
    $this->compiler->icon('regular', 'new-icon');
    file_put_contents($this->compiler->root.'/resources/svg/README.md', 'keep');
    mkdir($this->compiler->root.'/resources/svg/custom.svg');

    expect($this->compiler->run()->isSuccessful())->toBeTrue();
    $after = $this->compiler->outputs();
    expect($after)->not->toHaveKey('old.svg');
    expect($after)->toHaveKey('new-icon.svg');
    expect($after['heart.svg'])->not->toBe($before['heart.svg']);
    expect($after['enum'])->not->toContain('case Old');
    expect($after['enum'])->toContain("case NewIcon = 'disarto-new-icon';");
    expect($after['README.md'])->toBe('keep');
    expect(is_dir($this->compiler->root.'/resources/svg/custom.svg'))->toBeTrue();
});

it('fails before changing outputs for invalid source input', function (string $scenario, string $error) {
    expect($this->compiler->run()->isSuccessful())->toBeTrue();
    $before = $this->compiler->outputs();
    $source = $this->compiler->root.'/node_modules/disarto-icons-static/icons';

    match ($scenario) {
        'missing' => rename($source.'/solid', $source.'/missing'),
        'empty' => unlink($source.'/solid/heart.svg'),
        'malformed' => $this->compiler->icon('solid', 'broken', '<svg><path></svg>'),
        'invalid-name' => $this->compiler->icon('solid', 'bad_name'),
        'filename-collision' => $this->compiler->icon('regular', 'heart-solid'),
        'case-collision' => $this->compiler->icon('regular', 'he-art'),
    };

    $result = $this->compiler->run();
    expect($result->isSuccessful())->toBeFalse();
    expect($result->getErrorOutput())->toContain($error);
    expect($this->compiler->outputs())->toBe($before);
})->with([
    'missing variant' => ['missing', 'ENOENT'],
    'empty variant' => ['empty', 'No SVG icons found'],
    'malformed SVG' => ['malformed', 'Unexpected close tag'],
    'invalid enum identifier' => ['invalid-name', 'Cannot generate a PHP enum case'],
    'duplicate filename' => ['filename-collision', 'Duplicate output filename or enum case'],
    'duplicate case ignoring capitalization' => ['case-collision', 'Duplicate output filename or enum case'],
]);
