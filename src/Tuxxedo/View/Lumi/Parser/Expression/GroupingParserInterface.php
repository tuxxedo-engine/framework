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

interface GroupingParserInterface
{
    /**
     * @throws ParserException
     */
    public function parseGroup(): void;

    /**
     * @throws ParserException
     */
    public function parseDereferenceChain(
        TokenInterface $caller,
        TokenInterface $method,
    ): void;
}
