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

namespace Tuxxedo\Debug\Config;

enum DebugEditor: string
{
    case PHPSTORM = 'phpstorm';
    case VSCODE = 'vscode';
    case SUBLIME = 'sublime';
    case TEXTMATE = 'textmate';
    case IDEA = 'idea';

    /**
     * @codeCoverageIgnore
     */
    public function formatUrl(
        string $file,
        int $line,
    ): string {
        return match ($this) {
            self::PHPSTORM => 'phpstorm://open?file=' . $file . '&line=' . \strval($line),
            self::VSCODE => 'vscode://file/' . $file . ':' . \strval($line),
            self::SUBLIME => 'subl://open?url=file://' . $file . '&line=' . \strval($line),
            self::TEXTMATE => 'txmt://open/?url=file://' . $file . '&line=' . \strval($line),
            self::IDEA => 'idea://open?file=' . $file . '&line=' . \strval($line),
        };
    }
}
