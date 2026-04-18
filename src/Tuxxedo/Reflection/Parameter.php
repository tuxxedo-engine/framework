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

namespace Tuxxedo\Reflection;

readonly class Parameter implements ParameterInterface
{
    public function __construct(
        public \ReflectionParameter $reflector,
        private TypeHelperInterface $typeHelper = new TypeHelper(),
    ) {
    }

    public function getDefaultType(): ?string
    {
        return $this->typeHelper->getDefaultType($this->reflector);
    }

    public function getBuiltinType(): ?string
    {
        return $this->typeHelper->getBuiltinType($this->reflector);
    }

    public function isNullable(): bool
    {
        return $this->typeHelper->isNullable($this->reflector);
    }
}
