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

use Tuxxedo\View\Lumi\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\BinaryOperator;
use Tuxxedo\View\Lumi\Syntax\UnaryOperator;
use Tuxxedo\View\Lumi\Token\TokenInterface;

interface OperatorParserInterface
{
    /**
     * @throws ParserException
     */
    public function parseBinaryByNode(
        ExpressionNodeInterface $left,
        BinaryOperator $operator,
    ): void;

    /**
     * @throws ParserException
     */
    public function parseBinaryByToken(
        TokenInterface $left,
        BinaryOperator $operator,
    ): void;

    /**
     * @throws ParserException
     */
    public function parseUnary(
        UnaryOperator $operator,
        TokenInterface $operand,
    ): void;

    /**
     * @param ExpressionNodeInterface[] $operands
     */
    public function parseConcat(
        array $operands,
    ): void;
}
