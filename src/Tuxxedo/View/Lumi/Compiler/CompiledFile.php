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

readonly class CompiledFile implements CompiledFileInterface
{
    public function __construct(
        public string $name,
        public string $source,
    ) {
    }

    public function save(
        string $path,
        string $extension = '.php',
    ): void {
        if (!$this->saveTo($path, $extension)) {
            throw CompilerException::fromCannotSave(
                name: $this->name,
                path: $path . $this->name . $extension,
            );
        }
    }

    public function saveTo(
        string $path,
        string $extension = '.php',
    ): bool {
        return @\file_put_contents(
            filename: $path . $this->name . $extension,
            data: $this->source,
        ) !== false;
    }
}
