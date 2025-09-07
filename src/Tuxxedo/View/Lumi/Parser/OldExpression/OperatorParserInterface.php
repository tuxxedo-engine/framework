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

namespace Tuxxedo\View\Lumi\Parser\OldExpression;

use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;
use Tuxxedo\View\Lumi\Syntax\Token\TokenInterface;

interface OperatorParserInterface
{
    /**
     * @throws ParserException
     */
    public function parseBinaryByNode(
        ExpressionNodeInterface $left,
        BinarySymbol $operator,
    ): void;

    /**
     * @throws ParserException
     */
    public function parseBinaryByToken(
        TokenInterface $left,
        BinarySymbol $operator,
    ): void;

    /**
     * @throws ParserException
     */
    public function parseUnary(
        UnarySymbol $operator,
        TokenInterface $operand,
    ): void;

    /**
     * @param ExpressionNodeInterface[] $operands
     */
    public function parseConcat(
        array $operands,
    ): void;
}
