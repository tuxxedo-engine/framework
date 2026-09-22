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

namespace Tuxxedo\Console\Output;

use Tuxxedo\Console\ConsoleException;

interface TableInterface
{
    /**
     * @var list<string>
     */
    public array $headers {
        get;
    }

    /**
     * @var list<list<string>>
     */
    public array $rows {
        get;
    }

    /**
     * @throws ConsoleException
     */
    public function render(
        OutputInterface $output,
    ): void;
}
