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

namespace Tuxxedo\View\Lumi\Parser;

use Tuxxedo\View\Lumi\Lexer\Token\TokenInterface;
use Tuxxedo\View\Lumi\Lexer\Token\TokenTypeInterface;

class ParserContext implements ParserContextInterface
{
    public private(set) int $level = 0;
    public private(set) ?array $expects = null;
    public private(set) array $blocks = [];

    public function isExpected(
        \UnitEnum&TokenTypeInterface $tokenType,
    ): bool {
        return $this->expects === null || \in_array($tokenType, $this->expects, true);
    }

    public function expects(
        \UnitEnum&TokenTypeInterface ...$tokenType,
    ): self {
        $this->expects = $tokenType;

        return $this;
    }

    public function expectsAny(): self
    {
        $this->expects = null;

        return $this;
    }

    public function append(
        TokenInterface $token,
    ): self {
        $this->blocks[$this->level] ??= [];
        $this->blocks[$this->level][] = $token;

        return $this;
    }

    public function push(): self
    {
        $this->level++;

        return $this;
    }

    public function pop(): array
    {
        $blocks = $this->blocks[$this->level] ?? [];

        unset($this->blocks[$this->level]);
        $this->level--;

        if ($this->level < 0) {
            throw ParserException::fromStateLevelMismatch();
        }

        return $blocks;
    }
}
