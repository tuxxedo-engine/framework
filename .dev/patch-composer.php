<?php

if ($argc !== 2) {
    die('Error: Must be called with exactly one argument');
}

foreach (glob('../vendor/bin/*.bat') as $batFile) {
    $lines = file($batFile);

    patch_bat_file($lines, $argv[1]);

    file_put_contents($batFile, join(PHP_EOL, $lines));
}

function patch_bat_file(array &$lines, string $phpBinary): void
{
    foreach ($lines as &$line) {
        if (str_starts_with($line, 'php ')) {
            $line = substr_replace($line, $phpBinary, 0, 3);
        }
    }
}
