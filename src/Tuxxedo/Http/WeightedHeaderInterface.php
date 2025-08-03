<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Http;

interface WeightedHeaderInterface extends HeaderInterface
{
    /**
     * @return string[]
     */
    public function getWeightedOrder(): array;

    /**
     * @return array<array{value: string, weight: float}>
     */
    public function getWeightedPairs(): array;
}
