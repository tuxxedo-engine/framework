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

namespace Support\Reflection;

use Tuxxedo\Reflection\ParameterReflectorInterface;

class StubParameterReflector implements ParameterReflectorInterface
{
    public \ReflectionParameter $reflector {
        get {
            throw new \LogicException('Not implemented in stub');
        }
    }

    public string $name {
        get {
            return 'stub';
        }
    }

    /**
     * @param class-string|null $defaultType
     */
    public function __construct(
        private readonly ?string $defaultType = null,
    ) {
    }

    public function getDefaultType(): ?string
    {
        return $this->defaultType;
    }

    public function getBuiltinType(): ?string
    {
        return null;
    }

    public function isNullable(): bool
    {
        return false;
    }

    public function hasAttribute(
        string $attribute,
    ): bool {
        return false;
    }

    public function getAttribute(
        string $attribute,
    ): object {
        throw new \LogicException('Not implemented in stub');
    }

    public function getAttributes(
        string $attribute,
    ): \Generator {
        return (static function (): \Generator {
            yield from [];
        })();
    }
}
