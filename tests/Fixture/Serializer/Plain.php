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

namespace Fixture\Serializer;

class Plain
{
    public static int $instances = 0;

    public int $count = 0;
    public ?string $label;
    public mixed $anything = null;
    public int|string $identifier = 0;
    /**
     * @var array<mixed>
     */
    public array $options = [];
    public ?object $payload = null;
    public ?\Countable $countable = null;
    public ?self $child = null;
    public bool $flag = false;
    public string $text = '';

    public true $agreed = true;

    /**
     * @var mixed
     */
    public $untyped;

    public string $uninitialized;
}
