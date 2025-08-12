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

interface CompiledFileBatchInterface
{
    /**
     * @var array<string, CompiledFileInterface>
     */
    public array $files {
        get;
    }

    /**
     * @throws CompilerException
     */
    public function save(
        string $path,
        string $extension = '.php',
    ): void;

    public function saveTo(
        string $path,
        string $extension = '.php',
    ): bool;
}
