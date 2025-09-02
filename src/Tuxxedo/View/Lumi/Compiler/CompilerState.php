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

use Tuxxedo\View\Lumi\Node\NodeInterface;
use Tuxxedo\View\Lumi\Runtime\Directive\DefaultDirectives;
use Tuxxedo\View\Lumi\Runtime\Directive\DirectivesInterface;

class CompilerState implements CompilerStateInterface
{
    public private(set) ?string $expects = null;
    public readonly DirectivesInterface&CompilerDirectivesInterface $directives;

    public function __construct()
    {
        $this->directives = new CompilerDirectives(
            directives: DefaultDirectives::defaults(),
        );
    }

    public function enter(
        string $kind,
    ): void {
        if ($this->expects !== null) {
            throw CompilerException::fromUnexpectedStateEnter(
                kind: $kind,
            );
        }

        $this->expects = $kind;
    }

    public function leave(
        string $kind,
    ): void {
        if ($this->expects === null) {
            throw CompilerException::fromUnexpectedStateLeave(
                kind: $kind,
            );
        }

        $this->expects = null;
    }

    public function swap(
        string $kind,
    ): string {
        if ($this->expects === null) {
            throw CompilerException::fromUnexpectedStateLeave(
                kind: $kind,
            );
        }

        $oldState = $this->expects;
        $this->expects = $kind;

        return $oldState;
    }

    public function valid(
        NodeInterface $node,
    ): bool {
        return $node->kind === $this->expects;
    }
}
