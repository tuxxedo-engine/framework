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
use Tuxxedo\Console\Output\Style\Style;

interface TableInterface
{
    /**
     * @var list<string|StyledCell>
     */
    public array $headers {
        get;
    }

    /**
     * @var list<list<string|StyledCell>>
     */
    public array $rows {
        get;
    }

    public ?TableCharacterSet $characters {
        get;
    }

    public ?Style $headerStyle {
        get;
    }

    public ?Style $borderStyle {
        get;
    }

    /**
     * @throws ConsoleException
     */
    public function render(
        OutputInterface $output,
    ): void;
}
