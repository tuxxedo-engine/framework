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

namespace Tuxxedo\Console\Kernel;

use Tuxxedo\Console\Descriptor\CommandDescriptorInterface;
use Tuxxedo\Console\Output\OutputInterface;
use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

#[DefaultImplementation(class: HelpFormatter::class, lifecycle: Lifecycle::SINGLETON)]
interface HelpFormatterInterface
{
    public function render(
        CommandDescriptorInterface $descriptor,
        OutputInterface $output,
    ): void;

    /**
     * @param list<CommandDescriptorInterface> $commands
     */
    public function renderIndex(
        array $commands,
        OutputInterface $output,
    ): void;
}
