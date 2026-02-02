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

namespace Tuxxedo\View\Lumi\Library\Filter;

use Tuxxedo\View\Lumi\Library\Directive\DirectivesInterface;
use Tuxxedo\View\ViewException;

interface FilterInterface
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
     * @throws ViewException
     */
    public function call(
        mixed $value,
        DirectivesInterface $directives,
    ): mixed;
}
