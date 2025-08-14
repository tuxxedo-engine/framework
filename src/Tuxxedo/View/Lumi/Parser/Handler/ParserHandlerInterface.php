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

namespace Tuxxedo\View\Lumi\Parser\Handler;

use Tuxxedo\View\Lumi\Lexer\Token\TokenInterface;
use Tuxxedo\View\Lumi\Lexer\Token\TokenTypeInterface;
use Tuxxedo\View\Lumi\Parser\Node\NodeInterface;

interface ParserHandlerInterface
{
    public \UnitEnum&TokenTypeInterface $tokenType {
        get;
    }

    /**
     * @return NodeInterface[]
     */
    public function parse(
        TokenInterface $token,
    ): array;
}
