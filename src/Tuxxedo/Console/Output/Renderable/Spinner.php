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

use Tuxxedo\Console\Output\OutputInterface;

class Spinner
{
    private const FRAMES = [
        '⣾',
        '⣽',
        '⣻',
        '⢿',
        '⡿',
        '⣟',
        '⣯',
        '⣷',
    ];

    private const TICK_THROTTLE_SECONDS = 0.08;

    private int $frame = 0;
    private float $lastTickTime = 0;
    private string $message;

    public function __construct(
        private readonly OutputInterface $output,
        string $message = '',
    ) {
        $this->message = $message;

        $this->draw();
    }

    public function tick(): void
    {
        if (!$this->output->isInteractive) {
            return;
        }

        $now = \microtime(true);

        if ($now - $this->lastTickTime < self::TICK_THROTTLE_SECONDS) {
            return;
        }

        $this->lastTickTime = $now;
        $this->frame = ($this->frame + 1) % \sizeof(self::FRAMES);

        $this->draw();
    }

    public function setMessage(
        string $message,
    ): void {
        $this->message = $message;

        $this->draw();
    }

    public function finish(): void
    {
        if ($this->output->isInteractive) {
            $this->output->write("\r\033[2K");
        }
    }

    private function draw(): void
    {
        if (!$this->output->isInteractive) {
            return;
        }

        $line = self::FRAMES[$this->frame];

        if ($this->message !== '') {
            $line .= ' ' . $this->message;
        }

        $this->output->write("\r\033[2K" . $line);
    }
}
