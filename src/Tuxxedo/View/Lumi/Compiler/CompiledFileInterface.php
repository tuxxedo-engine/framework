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

interface CompiledFileInterface
{
    public string $sourceFile {
        get;
    }

    public string $sourceCode {
        get;
    }

    /**
     * @throws CompilerException
     */
    public function save(
        string $file,
    ): void;

    public function saveTo(
        string $file,
    ): bool;
}
