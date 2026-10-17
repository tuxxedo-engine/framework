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

namespace Tuxxedo\Intl;

class Collator implements CollatorInterface
{
    private const string ROOT = 'root';

    /**
     * @var array<string, \Collator>
     */
    private array $collators = [];

    public function compare(
        string $first,
        string $second,
        ?LocaleInterface $locale = null,
    ): int {
        $tag = $locale->tag ?? self::ROOT;
        $collator = $this->collators[$tag] ??= new \Collator($tag);
        $result = $collator->compare($first, $second);

        if ($result === false) {
            // @codeCoverageIgnoreStart
            throw TranslationException::fromCollationFailure(
                tag: $tag,
            );
            // @codeCoverageIgnoreEnd
        }

        return $result;
    }
}
