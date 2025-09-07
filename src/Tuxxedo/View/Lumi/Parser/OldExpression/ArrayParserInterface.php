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
use Tuxxedo\View\Lumi\Syntax\Token\TokenInterface;

interface ArrayParserInterface
{
    /**
     * @throws ParserException
     */
    public function parseAccess(
        TokenInterface $variable,
    ): void;
}
