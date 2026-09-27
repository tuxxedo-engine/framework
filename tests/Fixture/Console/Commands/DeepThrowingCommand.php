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

use Tuxxedo\Console\Attribute\Command;
use Tuxxedo\Console\ExitCode;

class DeepThrowingCommand
{
    public const int CHAIN_DEPTH = 8;

    #[Command('demo:deep-throw')]
    public function run(): ExitCode
    {
        $chain = new \RuntimeException('root');

        for ($i = 0; $i < self::CHAIN_DEPTH; $i++) {
            $chain = new \RuntimeException(
                message: 'layer-' . $i,
                previous: $chain,
            );
        }

        throw $chain;
    }
}
