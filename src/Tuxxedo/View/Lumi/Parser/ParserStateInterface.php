<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\View\Lumi\Parser;

interface ParserStateInterface
{
    public int $loopDepth {
        get;
    }

    public int $nestingDepth {
        get;
    }

    public function enterLoop(): void;

    /**
     * @throws ParserException
     */
    public function leaveLoop(): void;

    public function enterNesting(): void;

    /**
     * @throws ParserException
     */
    public function leaveNesting(): void;
}
