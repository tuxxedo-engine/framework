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

namespace Tuxxedo\Console\Output\Renderable;

use Tuxxedo\Console\ConsoleException;
use Tuxxedo\Console\Output\OutputInterface;

interface RenderableInterface
{
    /**
     * @throws ConsoleException
     */
    public function renderTo(
        OutputInterface $output,
    ): void;
}
