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
use Tuxxedo\View\Lumi\Node\FunctionCallNode;
use Tuxxedo\View\Lumi\Token\TokenInterface;

interface InvocationParserInterface
{
    public function parseSimpleFunction(
        TokenInterface $caller,
    ): FunctionCallNode;

    public function parseFunction(
        TokenInterface $caller,
    ): ExpressionNodeInterface;

    public function parseMethodCall(
        TokenInterface $caller,
        TokenInterface $method,
    ): ExpressionNodeInterface;

    public function parseDereferenceChain(
        TokenInterface $caller,
        TokenInterface $method,
    ): ExpressionNodeInterface;
}
