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

class ProgressBar
{
    private const BAR_WIDTH = 30;
    private const REDRAW_THROTTLE_SECONDS = 0.03;
    private const NON_INTERACTIVE_MILESTONE_PERCENT = 25;

    private int $current = 0;
    private float $lastDrawTime = 0;
    private int $lastMilestone = -1;
    private string $message = '';

    public function __construct(
        private readonly OutputInterface $output,
        private readonly int $total,
    ) {
        $this->draw();
    }

    /**
     * @template TKey
     * @template TValue
     *
     * @param array<TKey, TValue> $items
     *
     * @return \Generator<TKey, TValue>
     */
    public function iterate(
        array $items,
    ): \Generator {
        try {
            foreach ($items as $key => $item) {
                yield $key => $item;

                $this->advance();
            }
        } finally {
            $this->finish();
        }
    }

    public function advance(
        int $steps = 1,
    ): void {
        $this->current = \min($this->current + $steps, $this->total);

        if ($this->output->isInteractive) {
            $now = \microtime(true);

            if ($now - $this->lastDrawTime < self::REDRAW_THROTTLE_SECONDS && $this->current < $this->total) {
                return;
            }

            $this->lastDrawTime = $now;

            $this->draw();

            return;
        }

        $percent = $this->currentPercent();
        $milestone = \intdiv($percent, self::NON_INTERACTIVE_MILESTONE_PERCENT) * self::NON_INTERACTIVE_MILESTONE_PERCENT;

        if ($milestone > $this->lastMilestone) {
            $this->lastMilestone = $milestone;

            $this->draw();
        }
    }

    public function setMessage(
        string $message,
    ): void {
        $this->message = $message;

        if ($this->output->isInteractive) {
            $this->draw();
        }
    }

    public function finish(): void
    {
        $this->current = $this->total;

        $this->draw();
        $this->output->line();
    }

    private function currentPercent(): int
    {
        return $this->total > 0
            ? (int) (($this->current / $this->total) * 100)
            : 100;
    }

    private function draw(): void
    {
        $percent = $this->currentPercent();
        $filled = (int) (($percent / 100) * self::BAR_WIDTH);
        $bar = \str_repeat('=', $filled) .
            ($filled < self::BAR_WIDTH ? '>' : '') .
            \str_repeat(' ', \max(0, self::BAR_WIDTH - $filled - 1));

        $line = \sprintf(
            '[%s] %3d%%',
            $bar,
            $percent,
        );

        if ($this->message !== '') {
            $line .= ' ' . $this->message;
        }

        if ($this->output->isInteractive) {
            $this->output->write("\r\033[2K" . $line);

            return;
        }

        $this->output->line($line);
    }
}
