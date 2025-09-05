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

use Tuxxedo\View\Lumi\Runtime\Directive\DirectivesInterface;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;

interface CompilerStateInterface
{
    public ?string $expects {
        get;
    }

    public DirectivesInterface&CompilerDirectivesInterface $directives {
        get;
    }

    /**
     * @throws CompilerException
     */
    public function enter(
        string $kind,
    ): void;

    /**
     * @throws CompilerException
     */
    public function leave(
        string $kind,
    ): void;

    /**
     * @throws CompilerException
     */
    public function swap(
        string $kind,
    ): string;

    public function valid(
        NodeInterface $node,
    ): bool;
}
