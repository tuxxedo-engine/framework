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

namespace Fixture\View\Lumi\Lexer\Handler\BlockTokenHandler;

use Tuxxedo\View\Lumi\Lexer\Handler\Block\BlockHandlerInterface;

class SpyBlockHandler implements BlockHandlerInterface
{
    use SpyBlockHandlerTrait;

    public function __construct(
        public string $directive,
    ) {
    }
}
