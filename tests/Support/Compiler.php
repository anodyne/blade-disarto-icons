<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final class Compiler
{
    public string $root;

    public const SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><!-- removable metadata --><path fill="currentColor" d="M 0 0 L 24 0 L 24 24 Z"/></svg>';

    public function __construct()
    {
        $this->root = sys_get_temp_dir().'/disarto-test-'.bin2hex(random_bytes(8));

        foreach (['bin', 'src', 'node_modules/disarto-icons-static/icons/regular', 'node_modules/disarto-icons-static/icons/solid'] as $directory) {
            mkdir($this->root.'/'.$directory, 0777, true);
        }

        copy(__DIR__.'/../../bin/compile-icons.mjs', $this->root.'/bin/compile-icons.mjs');
        symlink(realpath(__DIR__.'/../../node_modules/svgo'), $this->root.'/node_modules/svgo');
        $this->icon('regular', 'heart');
        $this->icon('solid', 'heart');
    }

    public function icon(string $variant, string $name, string $svg = self::SVG): void
    {
        file_put_contents($this->root.'/node_modules/disarto-icons-static/icons/'.$variant.'/'.$name.'.svg', $svg);
    }

    public function run(): Process
    {
        // Run outside the project to verify paths are relative to the script.
        $process = new Process(['bun', $this->root.'/bin/compile-icons.mjs'], sys_get_temp_dir());
        $process->setTimeout(30);
        $process->run();

        return $process;
    }

    public function outputs(): array
    {
        $outputs = [];

        foreach (glob($this->root.'/resources/svg/*') ?: [] as $path) {
            if (is_file($path)) {
                $outputs[basename($path)] = file_get_contents($path);
            }
        }

        $outputs['enum'] = file_get_contents($this->root.'/src/DisartoIcon.php');

        return $outputs;
    }

    public function cleanup(): void
    {
        (new Filesystem)->deleteDirectory($this->root);
    }
}
