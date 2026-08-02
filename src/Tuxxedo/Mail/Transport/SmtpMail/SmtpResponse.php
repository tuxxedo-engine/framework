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

namespace Tuxxedo\Mail\Transport\SmtpMail;

class SmtpResponse
{
    public bool $isSuccess {
        get {
            return $this->code >= 200 && $this->code < 300;
        }
    }

    public bool $isIntermediate {
        get {
            return $this->code >= 300 && $this->code < 400;
        }
    }

    public bool $isTransientFailure {
        get {
            return $this->code >= 400 && $this->code < 500;
        }
    }

    public bool $isPermanentFailure {
        get {
            return $this->code >= 500 && $this->code < 600;
        }
    }

    public string $summary {
        get {
            return $this->lines[0] ?? '';
        }
    }

    /**
     * @param list<string> $lines
     */
    public function __construct(
        public readonly int $code,
        public readonly array $lines,
    ) {
    }
}
