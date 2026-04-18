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

namespace Tuxxedo\View\Lumi\Runtime\Library\Function;

use Tuxxedo\View\Lumi\Library\Function\FunctionInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeFrameInterface;
use Tuxxedo\View\ViewException;

class DirectiveFunction implements FunctionInterface
{
    public private(set) string $name = 'directive';
    public private(set) array $aliases = [];

    /**
     * @param \Closure(): RuntimeFrameInterface $frame
     */
    public function call(
        array $arguments,
        \Closure $frame,
    ): string|int|float|bool|null {
        /** @var string $directive */
        $directive = $arguments[0];

        $frame = $frame();

        if (!$frame->hasDirective($directive)) {
            throw ViewException::fromInvalidDirective(
                directive: $directive,
            );
        }

        return $frame->directive($directive);
    }
}
