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

namespace Tuxxedo\View\Lumi\Lexer;

enum LexerStateFlag: int
{
    case NONE = 0;
    case TEXT_AS_RAW = 1;

    public function onRemove(
        LexerStateInterface $state,
    ): void {
        if ($this === self::TEXT_AS_RAW) {
            $state->setTextAsRawBuffer('');
            $state->setTextAsRawEndSequence(null);
            $state->setTextAsRawEndDirective(null);
        }
    }
}
