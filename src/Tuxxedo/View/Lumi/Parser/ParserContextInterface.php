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

interface ParserContextInterface
{
    public int $level {
        get;
    }

    /**
     * @var string[]|null
     */
    public ?array $expects {
        get;
    }

    /**
     * @var array<int, TokenInterface[]>
     */
    public array $blocks {
        get;
    }

    public function isExpected(
        string $tokenName,
    ): bool;

    public function expects(
        string ...$tokenName,
    ): self;

    public function expectsAny(): self;

    public function append(
        TokenInterface $token,
    ): self;

    public function push(): self;

    /**
     * @return TokenInterface[]
     *
     * @throws ParserException
     */
    public function pop(): array;
}
