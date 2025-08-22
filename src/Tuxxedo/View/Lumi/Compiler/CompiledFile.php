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
        public string $sourceFile,
        public string $sourceCode,
    ) {
    }

    public function save(
        string $file,
    ): void {
        if (!$this->saveTo($file)) {
            throw CompilerException::fromCannotSave(
                name: $this->sourceFile,
                path: $file,
            );
        }
    }

    public function saveTo(
        string $file,
    ): bool {
        return @\file_put_contents(
            filename: $file,
            data: $this->sourceCode,
        ) !== false;
    }
}
