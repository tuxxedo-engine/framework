<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Support\Intl;

class CatalogueScratchpad
{
    public readonly string $root;

    /**
     * @var list<string>
     */
    private array $createdPaths = [];

    public function __construct()
    {
        $this->root = \str_replace('\\', '/', \sys_get_temp_dir()) . '/tuxxedo_intl_' . \uniqid('', true);

        \mkdir($this->root, 0755, true);

        $this->createdPaths[] = $this->root;
    }

    public function write(
        string $localeTag,
        string $group,
        string $returnExpression,
    ): string {
        $directory = $this->root . '/' . $localeTag;

        if (!\is_dir($directory)) {
            \mkdir($directory, 0755, true);

            $this->createdPaths[] = $directory;
        }

        $file = $directory . '/' . $group . '.php';

        \file_put_contents(
            $file,
            "<?php\n\nreturn " . $returnExpression . ";\n",
        );

        $this->createdPaths[] = $file;

        return $file;
    }

    public function cleanup(): void
    {
        foreach (\array_reverse($this->createdPaths) as $path) {
            if (\is_file($path)) {
                @\unlink($path);

                continue;
            }

            if (\is_dir($path)) {
                @\rmdir($path);
            }
        }

        $this->createdPaths = [];
    }

    public function __destruct()
    {
        $this->cleanup();
    }
}
