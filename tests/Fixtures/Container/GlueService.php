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

namespace Fixtures\Container;

use Tuxxedo\Container\Resolver\Glue;
use Tuxxedo\Container\Resolver\Tagged;

readonly class GlueService
{
    public function __construct(
        #[Glue(
            static function (): string {
                return 'Hello World';
            },
        )] public string $greeting,
        #[Glue(
            static function (OptionalService $service): string {
                return $service->secret;
            },
        )] public string $secret,
        #[Glue(
            static function (#[Tagged(TaggedServiceInterface::class)] array $services): int {
                return \sizeof($services);
            },
        )] public int $numberOfServices,
    ) {
    }
}
