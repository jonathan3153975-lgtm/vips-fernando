<?php

declare(strict_types=1);

/**
 * Verificacao de sintaxe PHP sem dependencias externas.
 *
 * Percorre os diretorios versionados do projeto e executa `php -l` em cada
 * arquivo, falhando o processo quando encontrar erro de sintaxe.
 */

$root = dirname(__DIR__);
$targets = [
    'app',
    'bootstrap',
    'config',
    'database',
    'public',
    'routes',
    'tests',
    'tools',
];
$files = ['composer.json' => true];

foreach ($targets as $directory) {
    $path = $root . '/' . $directory;

    if (!is_dir($path)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[substr($file->getPathname(), strlen($root) + 1)] = true;
        }
    }
}

ksort($files);
$checked = 0;
$failures = [];

foreach (array_keys($files) as $relativePath) {
    $output = [];
    $exitCode = 0;

    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($root . '/' . $relativePath) . ' 2>&1', $output, $exitCode);
    $checked++;

    if ($exitCode === 0) {
        continue;
    }

    $failures[$relativePath] = implode(PHP_EOL, $output);
}

if ($failures !== []) {
    fwrite(STDERR, PHP_EOL . 'Falhas de sintaxe:' . PHP_EOL);

    foreach ($failures as $path => $message) {
        fwrite(STDERR, '  ' . $path . PHP_EOL . '    ' . $message . PHP_EOL);
    }

    fwrite(STDERR, PHP_EOL . count($failures) . ' de ' . $checked . ' arquivo(s) com erro.' . PHP_EOL);
    exit(1);
}

echo 'Sintaxe validada em ' . $checked . ' arquivo(s).' . PHP_EOL;
