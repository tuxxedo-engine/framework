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

namespace Tuxxedo\Logger\Formatter;

use Tuxxedo\Logger\LogFailureMode;
use Tuxxedo\Serializer\SerializerException;

enum LogFormat
{
    case LINE;
    case JSON;

    /**
     * @throws SerializerException
     */
    public function createFormatter(
        LogFailureMode $unencodable = LogFailureMode::MARKER,
    ): LogFormatterInterface {
        return match ($this) { // @codeCoverageIgnore
            self::LINE => new LineFormatter(),
            self::JSON => new JsonFormatter(
                unencodable: $unencodable,
            ),
        };
    }
}
