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

use Tuxxedo\Console\Output\Style\Style;

class Spinner implements SpinnerInterface
{
    private readonly SpinnerTheme $theme;
    private readonly float $throttleSeconds;

    private int $frame = 0;
    private float $lastTickTime = 0;

    private string $message;

    public function __construct(
        private readonly OutputInterface $output,
        ?SpinnerTheme $theme = null,
        string $message = '',
    ) {
        $this->theme = $theme ?? SpinnerTheme::default();
        $this->throttleSeconds = $this->theme->frames->interval->seconds + $this->theme->frames->interval->nanoseconds / 1_000_000_000;
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
        $this->frame = ($this->frame + 1) % \sizeof($this->theme->frames->frames);

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
                $this->writePart(
                    bytes: $this->message,
                    style: $this->theme->messageStyle,
                );

                $this->output->line();
            }

            return;
        }

        $this->output->write("\r\033[2K");

        $this->writePart(
            bytes: $this->theme->frames->frames[$this->frame],
            style: $this->theme->frameStyle,
        );

        if ($this->message !== '') {
            $this->writePart(
                bytes: ' ' . $this->message,
                style: $this->theme->messageStyle,
            );
        }
    }

    private function writePart(
        string $bytes,
        ?Style $style,
    ): void {
        if ($style === null) {
            $this->output->write($bytes);

            return;
        }

        $this->output->styled($bytes, $style);
    }
}
