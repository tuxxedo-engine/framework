<?php

if ($argc !== 2) {
    die('Error: Must be called with exactly one argument');
}

foreach (glob('../vendor/bin/*.bat') as $batFile) {
    $replaced = false;
    $lines = file($batFile);

    patch_bat_file($lines, $argv[1], $replaced);

    file_put_contents($batFile, join(PHP_EOL, $lines));

    if ($replaced) {
        printf(
            "Patched %s\n",
            realpath($batFile),
        );
    }
}

function patch_bat_file(
    array &$lines,
    string $phpBinary,
    bool &$replaced,
): void {
    foreach (\array_keys($lines) as $lineNumber) {
        $line = trim($lines[$lineNumber]);

        if (str_starts_with($line, 'php ')) {
            $lines[$lineNumber] = substr_replace($line, $phpBinary, 0, 3);

            $replaced = true;
        } else {
            $lines[$lineNumber] = $line;
        }
    }
}
