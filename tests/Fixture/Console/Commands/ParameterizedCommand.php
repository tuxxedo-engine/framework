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

namespace Fixture\Console\Commands;

use Tuxxedo\Console\Attribute\Argument;
use Tuxxedo\Console\Attribute\Command;
use Tuxxedo\Console\Attribute\Flag;
use Tuxxedo\Console\Attribute\Option;
use Tuxxedo\Console\ExitCode;

class ParameterizedCommand
{
    /**
     * @param list<string> $tags
     */
    #[Command('demo:params')]
    public function run(
        #[Argument(description: 'Positional target')]
        string $target,
        #[Option(short: 'n', description: 'How many')]
        int $count = 1,
        #[Option(short: 't', repeatable: true)]
        array $tags = [],
        #[Flag(short: 'f')]
        bool $force = false,
    ): ExitCode {
        return ExitCode::SUCCESS;
    }
}
