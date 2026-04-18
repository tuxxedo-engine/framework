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
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;
use Tuxxedo\View\ViewException;

class DirectiveFunction implements FunctionInterface
{
    public private(set) string $name = 'directive';
    public private(set) array $aliases = [];

    /**
     * @param \Closure(): RuntimeContextInterface $context
     */
    public function call(
        array $arguments,
        \Closure $context,
    ): string|int|float|bool|null {
        /** @var string $directive */
        $directive = $arguments[0];

        $context = $context();

        if (!$context->hasDirective($directive)) {
            throw ViewException::fromInvalidDirective(
                directive: $directive,
            );
        }

        return $context->directive($directive);
    }
}
