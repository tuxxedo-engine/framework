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

namespace Tuxxedo\Mail\Serializer\Mime;

use Tuxxedo\Mail\HeaderInterface;

class MultipartMimePart implements MimePartInterface
{
    public string $mimeType {
        get {
            return 'multipart/' . $this->subtype->value;
        }
    }

    /**
     * @param list<MimePartInterface> $children
     * @param list<HeaderInterface> $headers
     */
    public function __construct(
        public readonly MultipartSubtype $subtype,
        public readonly string $boundary,
        public readonly array $children,
        public readonly array $headers = [],
    ) {
    }
}
