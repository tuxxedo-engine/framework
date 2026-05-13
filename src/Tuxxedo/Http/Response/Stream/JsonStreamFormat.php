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

namespace Tuxxedo\Http\Response\Stream;

enum JsonStreamFormat
{
    case JSONL;
    case RFC7464;

    public function getContentType(): string
    {
        return match ($this) {
            self::JSONL => 'application/x-ndjson',
            self::RFC7464 => 'application/json-seq',
        };
    }
}
