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

namespace Tuxxedo\View\Lumi\Library\Function;

use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;
use Tuxxedo\View\ViewException;

interface FunctionInterface
{
    public string $name {
        get;
    }

    /**
     * @var string[]
     */
    public array $aliases {
        get;
    }

    /**
     * @param mixed[] $arguments
     * @param \Closure(): RuntimeContextInterface $context
     *
     * @throws ViewException
     */
    public function call(
        array $arguments,
        \Closure $context,
    ): mixed;
}
