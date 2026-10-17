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
    private const string ROOT = 'und';

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

    public function sort(
        array $values,
        ?LocaleInterface $locale = null,
    ): array {
        \uasort(
            $values,
            fn (mixed $first, mixed $second): int => $this->compareValues(
                first: $first,
                second: $second,
                locale: $locale,
            ),
        );

        return $values;
    }

    public function sortKeys(
        array $values,
        ?LocaleInterface $locale = null,
    ): array {
        \uksort(
            $values,
            fn (int|string $first, int|string $second): int => $this->compareValues(
                first: $first,
                second: $second,
                locale: $locale,
            ),
        );

        return $values;
    }

    /**
     * @throws TranslationException
     */
    private function compareValues(
        mixed $first,
        mixed $second,
        ?LocaleInterface $locale,
    ): int {
        if (\is_string($first) && \is_string($second)) {
            return $this->compare(
                first: $first,
                second: $second,
                locale: $locale,
            );
        }

        return $first <=> $second;
    }
}
