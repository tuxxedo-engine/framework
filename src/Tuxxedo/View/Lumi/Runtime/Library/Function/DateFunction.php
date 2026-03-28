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

namespace Tuxxedo\View\Lumi\Runtime\Library\Function;

use Tuxxedo\View\Lumi\Library\Directive\DirectivesInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionInterface;
use Tuxxedo\View\ViewRenderInterface;

class DateFunction implements FunctionInterface
{
    public private(set) string $name = 'date';
    public private(set) array $aliases = [
        'time',
    ];

    public function call(
        array $arguments,
        ViewRenderInterface $render,
        DirectivesInterface $directives,
    ): string {
        /** @var string $format */
        $format = $arguments[0];

        /** @var int $timestamp */
        $timestamp = $arguments[1] ?? \time();

        return \date($format, $timestamp);
    }
}
