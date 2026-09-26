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

    public function styled(
        string $bytes,
        Style $style,
    ): void {
        $this->stream->write(
            $this->shouldDecorate()
                ? $style->apply($bytes)
                : $bytes,
        );
    }

    public function error(
        string $bytes,
    ): void {
        $this->styled($bytes, Style::error());
    }

    public function success(
        string $bytes,
    ): void {
        $this->styled($bytes, Style::success());
    }

    public function warning(
        string $bytes,
    ): void {
        $this->styled($bytes, Style::warning());
    }

    public function info(
        string $bytes,
    ): void {
        $this->styled($bytes, Style::info());
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
            DecorationMode::AUTO => \getenv('NO_COLOR') === false &&
                (
                    \getenv('FORCE_COLOR') !== false ||
                    $this->stream->isTerminal
                ),
        };
    }
}
