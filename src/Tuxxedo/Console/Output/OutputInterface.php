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
use Tuxxedo\Console\Stream\OutputStreamInterface;

interface OutputInterface
{
    public bool $isInteractive {
        get;
    }

    public OutputStreamInterface $stream {
        get;
    }

    /**
     * @throws ConsoleException
     */
    public function write(
        string $bytes,
        ?Color $foreground = null,
        ?Color $background = null,
    ): void;

    /**
     * @throws ConsoleException
     */
    public function line(
        string $text = '',
        ?Color $foreground = null,
        ?Color $background = null,
    ): void;

    /**
     * @throws ConsoleException
     */
    public function styled(
        string $bytes,
        Style $style,
    ): void;

    /**
     * @throws ConsoleException
     */
    public function error(
        string $bytes,
    ): void;

    /**
     * @throws ConsoleException
     */
    public function success(
        string $bytes,
    ): void;

    /**
     * @throws ConsoleException
     */
    public function warning(
        string $bytes,
    ): void;

    /**
     * @throws ConsoleException
     */
    public function info(
        string $bytes,
    ): void;
}
