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

use Tuxxedo\Console\Stream\OutputStreamInterface;

class StreamOutput implements OutputInterface
{
    public bool $isInteractive {
        get {
            return $this->stream->isTerminal;
        }
    }

    public function __construct(
        public readonly OutputStreamInterface $stream,
        public readonly DecorationMode $decorationMode = DecorationMode::AUTO,
    ) {
    }

    public function write(
        string $bytes,
        ?Color $foreground = null,
        ?Color $background = null,
    ): void {
        $this->stream->write(
            $this->decorate(
                bytes: $bytes,
                foreground: $foreground,
                background: $background,
            ),
        );
    }

    public function line(
        string $text = '',
        ?Color $foreground = null,
        ?Color $background = null,
    ): void {
        $this->stream->write(
            $this->decorate(
                bytes: $text,
                foreground: $foreground,
                background: $background,
            ) . \PHP_EOL,
        );
    }

    private function decorate(
        string $bytes,
        ?Color $foreground,
        ?Color $background,
    ): string {
        if ($foreground === null && $background === null) {
            return $bytes;
        }

        if (!$this->shouldDecorate()) {
            return $bytes;
        }

        $codes = [];

        if ($foreground !== null) {
            $codes[] = $foreground->foreground();
        }

        if ($background !== null) {
            $codes[] = $background->background();
        }

        return "\033[" . \join(';', $codes) . 'm' . $bytes . "\033[0m";
    }

    private function shouldDecorate(): bool
    {
        return match ($this->decorationMode) {
            DecorationMode::ALWAYS => true,
            DecorationMode::NEVER => false,
            DecorationMode::AUTO => $this->stream->isTerminal && \getenv('NO_COLOR') === false,
        };
    }
}
