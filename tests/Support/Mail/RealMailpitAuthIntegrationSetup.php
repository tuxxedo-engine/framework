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

namespace Support\Mail;

use PHPUnit\Framework\TestCase;

/**
 * @mixin TestCase
 */
trait RealMailpitAuthIntegrationSetup
{
    abstract protected function mailpitAuthSkipReason(): ?string;

    protected function setUp(): void
    {
        $reason = $this->mailpitAuthSkipReason();

        if ($reason !== null) {
            self::markTestSkipped($reason);
        }

        parent::setUp();
    }
}
