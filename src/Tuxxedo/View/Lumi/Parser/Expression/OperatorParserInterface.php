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

namespace Tuxxedo\View\Lumi\Parser\Expression;

use Tuxxedo\View\Lumi\Syntax\BinaryOperator;
use Tuxxedo\View\Lumi\Syntax\UnaryOperator;
use Tuxxedo\View\Lumi\Token\TokenInterface;

interface OperatorParserInterface
{
    public function parseBinary(
        TokenInterface $left,
        BinaryOperator $operator,
    ): void;

    public function parseUnary(
        UnaryOperator $operator,
        TokenInterface $operand,
    ): void;
}
