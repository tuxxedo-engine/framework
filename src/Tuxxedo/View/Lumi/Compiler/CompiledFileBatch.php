<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\View\Lumi\Compiler;

readonly class CompiledFileBatch implements CompiledFileBatchInterface
{
    public array $files;

    /**
     * @param CompiledFileInterface[] $compiledFiles
     */
    public function __construct(
        array $compiledFiles,
    ) {
        $files = [];

        foreach ($compiledFiles as $compiledFile) {
            $files[$compiledFile->name] = $compiledFile;
        }

        $this->files = $files;
    }

    public function save(
        string $path,
        string $extension = '.php',
    ): void {
        foreach ($this->files as $compiledFile) {
            $compiledFile->save($path, $extension);
        }
    }

    public function saveTo(
        string $path,
        string $extension = '.php',
    ): bool {
        foreach ($this->files as $compiledFile) {
            if (!$compiledFile->saveTo($path, $extension)) {
                return false;
            }
        }

        return true;
    }
}
