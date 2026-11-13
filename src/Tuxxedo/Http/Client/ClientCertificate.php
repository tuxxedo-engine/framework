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

namespace Tuxxedo\Http\Client;

readonly class ClientCertificate implements ClientCertificateInterface
{
    public function __construct(
        public string $certificate,
        public ?string $key = null,
        #[\SensitiveParameter] public ?string $passphrase = null,
    ) {
    }
}
