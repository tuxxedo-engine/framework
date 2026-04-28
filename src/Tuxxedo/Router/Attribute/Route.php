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

namespace Tuxxedo\Router\Attribute;

use Tuxxedo\Http\Method;
use Tuxxedo\Router\PrefixInterface;
use Tuxxedo\Router\RoutePriority;

#[\Attribute(flags: \Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
readonly class Route
{
    /**
     * @var Method[]
     */
    public array $methods;

    /**
     * @param Method[]|string[] $method
     * @param class-string<PrefixInterface>|null $prefix
     */
    public function __construct(
        public ?string $uri = null,
        array $method = [],
        public ?string $name = null,
        public bool $trailingSlash = false,
        public ?string $prefix = null,
        public RoutePriority $priority = RoutePriority::NORMAL,
    ) {
        $this->methods = \array_map(
            static fn (Method|string $method): Method => \is_string($method)
                ? Method::from($method)
                : $method,
            $method,
        );
    }

    public function withName(
        ?string $name,
    ): static {
        return clone (
            $this,
            [
                'name' => $name,
            ],
        );
    }

    public function withUri(
        ?string $uri,
    ): static {
        return clone (
            $this,
            [
                'uri' => $uri,
                'name' => null,
            ],
        );
    }
}
