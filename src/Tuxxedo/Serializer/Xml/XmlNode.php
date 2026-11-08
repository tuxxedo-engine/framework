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

namespace Tuxxedo\Serializer\Xml;

class XmlNode
{
    /**
     * @var list<XmlNode>
     */
    public array $children = [];

    public string $text = '';

    /**
     * @param array<string, string> $attributes
     */
    public function __construct(
        public readonly string $name,
        public readonly array $attributes = [],
    ) {
    }
}
