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

namespace Tuxxedo\Console\Invocation;

use Tuxxedo\Console\ConsoleException;
use Tuxxedo\Console\Descriptor\CommandDescriptorInterface;

interface ArgvParserInterface
{
    /**
     * @param list<string> $argv
     *
     * @throws ConsoleException
     */
    public function parse(
        array $argv,
        CommandDescriptorInterface $descriptor,
    ): ParsedArgvInterface;
}
