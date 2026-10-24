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

namespace Tuxxedo\View\Lumi\Compiler;

use Tuxxedo\View\Lumi\Library\Directive\DirectivesInterface;
use Tuxxedo\View\Lumi\Library\Directive\MutableDirectives;
use Tuxxedo\View\Lumi\Library\Directive\MutableDirectivesInterface;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\NodeScope;

class CompilerState implements CompilerStateInterface
{
    public private(set) ?NodeScope $expects = null;
    public private(set) DirectivesInterface&MutableDirectivesInterface $directives;
    public private(set) int $flags = 0;
    private readonly DirectivesInterface&MutableDirectivesInterface $defaultDirectives;

    public function __construct(
        (DirectivesInterface&MutableDirectivesInterface)|null $directives = null,
    ) {
        $this->defaultDirectives = $directives ?? MutableDirectives::createWithDefaults();
        $this->directives = clone $this->defaultDirectives;
    }

    public function reset(): void
    {
        $this->expects = null;
        $this->directives = clone $this->defaultDirectives;
        $this->flags = 0;
    }

    public function hasFlag(
        CompilerStateFlag $flag,
    ): bool {
        return ($this->flags & $flag->value) !== 0;
    }

    public function flag(
        CompilerStateFlag $flag,
    ): void {
        $this->flags |= $flag->value;
    }

    public function removeFlag(
        CompilerStateFlag $flag,
    ): void {
        $this->flags = $this->flags & ~$flag->value;
    }

    public function is(
        NodeScope $scope,
    ): bool {
        return $scope === $this->expects;
    }

    public function enter(
        NodeScope $scope,
    ): void {
        if ($this->expects !== null) {
            throw CompilerException::fromUnexpectedStateEnter(
                scope: $scope,
            );
        }

        $this->expects = $scope;
    }

    public function leave(
        NodeScope $scope,
    ): void {
        if ($this->expects === null) {
            throw CompilerException::fromUnexpectedStateLeave(
                scope: $scope,
            );
        }

        $this->expects = null;
    }

    public function swap(
        NodeScope $scope,
    ): NodeScope {
        if ($this->expects === null) {
            throw CompilerException::fromUnexpectedStateLeave(
                scope: $scope,
            );
        }

        $oldState = $this->expects;
        $this->expects = $scope;

        return $oldState;
    }

    public function valid(
        NodeInterface $node,
    ): bool {
        return \in_array($this->expects, $node->scopes, true);
    }
}
