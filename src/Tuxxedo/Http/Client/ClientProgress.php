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

readonly class ClientProgress implements ClientProgressInterface
{
    public function __construct(
        public ClientRequestInterface $request,
        public int $downloaded = 0,
        public ?int $downloadTotal = null,
        public int $uploaded = 0,
        public ?int $uploadTotal = null,
    ) {
    }
}
