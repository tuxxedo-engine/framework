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

class Spinner implements SpinnerInterface
{
    private readonly FrameSequence $frames;
    private readonly float $throttleSeconds;

    private int $frame = 0;
    private float $lastTickTime = 0;

    private string $message;

    public function __construct(
        private readonly OutputInterface $output,
        ?FrameSequence $frames = null,
        string $message = '',
    ) {
        $this->frames = $frames ?? FrameSequence::brailleDots();
        $this->throttleSeconds = $this->frames->interval->seconds + $this->frames->interval->nanoseconds / 1_000_000_000;
        $this->message = $message;

        $this->draw();
    }

    public function tick(): void
    {
        if (!$this->output->isInteractive) {
            return;
        }

        $now = \microtime(true);

        if ($now - $this->lastTickTime < $this->throttleSeconds) {
            return;
        }

        $this->lastTickTime = $now;
        $this->frame = ($this->frame + 1) % \sizeof($this->frames->frames);

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
            if ($this->message !== '') {
                $this->output->line($this->message);
            }

            return;
        }

        $line = $this->frames->frames[$this->frame];

        if ($this->message !== '') {
            $line .= ' ' . $this->message;
        }

        $this->output->write("\r\033[2K" . $line);
    }
}
