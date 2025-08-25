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

use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Token\TokenInterface;

interface InvocationParserInterface
{
    /**
     * @throws ParserException
     */
    public function parseFunction(
        TokenInterface $caller,
    ): void;

    /**
     * @throws ParserException
     */
    public function parseMethodCall(
        TokenInterface $caller,
        TokenInterface $method,
    ): void;

    /**
     * @throws ParserException
     */
    public function parseDereferenceChain(
        TokenInterface $caller,
        TokenInterface $method,
    ): void;
}
