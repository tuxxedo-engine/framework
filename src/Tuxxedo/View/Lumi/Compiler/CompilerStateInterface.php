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

use Tuxxedo\View\Lumi\Library\Directive\DirectivesInterface;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\NodeScope;

interface CompilerStateInterface
{
    public ?NodeScope $expects {
        get;
    }

    public DirectivesInterface&CompilerDirectivesInterface $directives {
        get;
    }

    public int $flags {
        get;
    }

    public function hasFlag(
        CompilerStateFlag $flag,
    ): bool;

    public function flag(
        CompilerStateFlag $flag,
    ): void;

    public function removeFlag(
        CompilerStateFlag $flag,
    ): void;

    public function is(
        NodeScope $scope,
    ): bool;

    /**
     * @throws CompilerException
     */
    public function enter(
        NodeScope $scope,
    ): void;

    /**
     * @throws CompilerException
     */
    public function leave(
        NodeScope $scope,
    ): void;

    /**
     * @throws CompilerException
     */
    public function swap(
        NodeScope $scope,
    ): NodeScope;

    public function valid(
        NodeInterface $node,
    ): bool;
}
