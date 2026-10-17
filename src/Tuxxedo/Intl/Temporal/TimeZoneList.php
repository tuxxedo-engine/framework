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

namespace Tuxxedo\Intl\Temporal;

use Tuxxedo\Intl\Collator;
use Tuxxedo\Intl\CollatorInterface;
use Tuxxedo\Intl\LocaleInterface;
use Tuxxedo\Intl\TranslationException;

class TimeZoneList implements TimeZoneListInterface
{
    private ?\ResourceBundle $metaZones = null;

    /**
     * @var array<string, \ResourceBundle>
     */
    private array $zoneStrings = [];

    public function __construct(
        private readonly CollatorInterface $collator = new Collator(),
    ) {
    }

    public function all(
        LocaleInterface $locale,
        ?string $country = null,
        ?\DateTimeInterface $at = null,
    ): array {
        return $this->sortByOffset(
            entries: $this->entries(
                names: self::identifiers($country),
                locale: $locale,
                at: $at ?? new \DateTimeImmutable(),
            ),
            locale: $locale,
        );
    }

    public function grouped(
        LocaleInterface $locale,
        ?\DateTimeInterface $at = null,
    ): array {
        $at ??= new \DateTimeImmutable();
        $buckets = [];

        $entries = $this->entries(
            names: \DateTimeZone::listIdentifiers(),
            locale: $locale,
            at: $at,
        );

        foreach ($entries as $entry) {
            $key = $entry->metazone !== null
                ? $entry->metazone . '|' . self::seasonalOffsets($entry->name, $at)
                : 'zone|' . $entry->name;

            $buckets[$key][] = $entry;
        }

        $readerZones = $locale->region !== null && \preg_match('/^[A-Z]{2}$/', $locale->region) === 1
            ? \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, $locale->region)
            : [];

        $groups = [];

        foreach ($buckets as $entries) {
            $representative = $this->representative($entries);
            $members = $this->orderMembers(
                entries: $entries,
                representative: $representative,
                readerZones: $readerZones,
                locale: $locale,
            );

            $groups[] = new TimeZoneGroup(
                name: $representative->name,
                displayName: self::canonicalId($representative->name) !== null
                    ? self::icuDisplayName($representative->name, \IntlTimeZone::DISPLAY_LONG_GENERIC, $locale)
                    : $representative->displayName,
                offset: $representative->offset,
                cities: \array_map(
                    static fn (TimeZoneEntryInterface $member): string => $member->city,
                    $members,
                ),
                zones: $members,
            );
        }

        \usort(
            $groups,
            fn (TimeZoneGroupInterface $first, TimeZoneGroupInterface $second): int => $this->compareByOffset(
                firstOffset: $first->offset,
                firstName: $first->displayName,
                secondOffset: $second->offset,
                secondName: $second->displayName,
                locale: $locale,
            ),
        );

        return $groups;
    }

    public function of(
        array $names,
        LocaleInterface $locale,
        ?\DateTimeInterface $at = null,
    ): array {
        return $this->entries(
            names: $names,
            locale: $locale,
            at: $at ?? new \DateTimeImmutable(),
        );
    }

    /**
     * @return list<string>
     *
     * @throws TranslationException
     */
    private static function identifiers(
        ?string $country,
    ): array {
        if ($country === null) {
            return \DateTimeZone::listIdentifiers();
        }

        $code = \strtoupper($country);

        if (\preg_match('/^[A-Z]{2}$/', $code) !== 1) {
            throw TranslationException::fromInvalidCountry(
                country: $country,
            );
        }

        return \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, $code);
    }

    /**
     * @param list<string> $names
     * @return list<TimeZoneEntryInterface>
     *
     * @throws TranslationException
     */
    private function entries(
        array $names,
        LocaleInterface $locale,
        \DateTimeInterface $at,
    ): array {
        $entries = [];

        foreach ($names as $name) {
            $entries[] = $this->entry(
                name: $name,
                locale: $locale,
                at: $at,
            );
        }

        return $entries;
    }

    /**
     * @throws TranslationException
     */
    private function entry(
        string $name,
        LocaleInterface $locale,
        \DateTimeInterface $at,
    ): TimeZoneEntryInterface {
        $offset = self::offsetAt(
            name: $name,
            at: $at,
        );

        $canonical = self::canonicalId($name);
        $city = ($canonical !== null ? $this->exemplarCity($canonical, $locale) : null) ?? self::cityFromName($name);

        return new TimeZoneEntry(
            name: $name,
            displayName: $canonical !== null
                ? self::icuDisplayName($name, \IntlTimeZone::DISPLAY_GENERIC_LOCATION, $locale)
                : $city,
            city: $city,
            offset: $offset,
            region: \str_contains($name, '/')
                ? \explode('/', $name, 2)[0]
                : '',
            metazone: $canonical !== null
                ? $this->metazone($canonical, $at)
                : null,
        );
    }

    /**
     * @throws TranslationException
     */
    private static function offsetAt(
        string $name,
        \DateTimeInterface $at,
    ): string {
        return \DateTimeImmutable::createFromInterface($at)
            ->setTimezone(self::timeZone($name))
            ->format('P');
    }

    /**
     * @throws TranslationException
     */
    private static function seasonalOffsets(
        string $name,
        \DateTimeInterface $at,
    ): string {
        $timeZone = self::timeZone($name);
        $year = $at->format('Y');

        return (new \DateTimeImmutable($year . '-01-15 12:00:00', $timeZone))->format('P') .
            '/' .
            (new \DateTimeImmutable($year . '-07-15 12:00:00', $timeZone))->format('P');
    }

    /**
     * @throws TranslationException
     */
    private static function timeZone(
        string $name,
    ): \DateTimeZone {
        try {
            return new \DateTimeZone($name);
        } catch (\Exception) {
            throw TranslationException::fromUnknownTimeZone(
                name: $name,
            );
        }
    }

    private static function canonicalId(
        string $name,
    ): ?string {
        $canonical = \IntlTimeZone::getCanonicalID($name, $isSystemId);

        return $isSystemId === true
            ? $canonical
            : null;
    }

    private static function cityFromName(
        string $name,
    ): string {
        $segments = \explode('/', $name);

        return \str_replace('_', ' ', $segments[\sizeof($segments) - 1]);
    }

    private static function icuDisplayName(
        string $name,
        int $style,
        LocaleInterface $locale,
    ): string {
        return \IntlTimeZone::createTimeZone($name)->getDisplayName(false, $style, $locale->tag);
    }

    /**
     * @throws TranslationException
     */
    private function exemplarCity(
        string $canonical,
        LocaleInterface $locale,
    ): ?string {
        $city = self::lookup(
            $this->zoneStrings($locale),
            'zoneStrings',
            \str_replace('/', ':', $canonical),
            'ec',
        );

        return \is_string($city)
            ? $city
            : null;
    }

    /**
     * @throws TranslationException
     */
    private function metazone(
        string $canonical,
        \DateTimeInterface $at,
    ): ?string {
        $history = self::lookup(
            $this->metaZones(),
            'metazoneInfo',
            \str_replace('/', ':', $canonical),
        );

        if (!$history instanceof \ResourceBundle) {
            return null;
        }

        $moment = \DateTimeImmutable::createFromInterface($at)
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d H:i');

        /** @var \ResourceBundle $period */
        foreach ($history as $period) {
            /** @var string $metazone */
            $metazone = $period->get(0);
            $from = $period->get(1);
            $to = $period->get(2);

            if (!\is_string($from) || !\is_string($to)) {
                return $metazone;
            }

            if ($from <= $moment && $moment < $to) {
                return $metazone;
            }
        }

        return null;
    }

    /**
     * @param non-empty-list<TimeZoneEntryInterface> $entries
     *
     * @throws TranslationException
     */
    private function representative(
        array $entries,
    ): TimeZoneEntryInterface {
        $first = $entries[0];

        if ($first->metazone === null) {
            return $first;
        }

        $golden = self::lookup(
            $this->metaZones(),
            'mapTimezones',
            $first->metazone,
            '001',
        );

        if (\is_string($golden)) {
            foreach ($entries as $entry) {
                if ($entry->name === $golden || self::canonicalId($entry->name) === $golden) {
                    return $entry;
                }
            }
        }

        return $first;
    }

    /**
     * @param list<TimeZoneEntryInterface> $entries
     * @param list<string> $readerZones
     * @return list<TimeZoneEntryInterface>
     *
     * @throws TranslationException
     */
    private function orderMembers(
        array $entries,
        TimeZoneEntryInterface $representative,
        array $readerZones,
        LocaleInterface $locale,
    ): array {
        $rank = static fn (TimeZoneEntryInterface $entry): int => match (true) {
            \in_array($entry->name, $readerZones, true) => 0,
            $entry->name === $representative->name => 1,
            default => 2,
        };

        \usort(
            $entries,
            function (TimeZoneEntryInterface $first, TimeZoneEntryInterface $second) use ($rank, $locale): int {
                $order = $rank($first) <=> $rank($second);

                return $order !== 0
                    ? $order
                    : $this->collator->compare(
                        first: $first->city,
                        second: $second->city,
                        locale: $locale,
                    );
            },
        );

        return $entries;
    }

    /**
     * @param list<TimeZoneEntryInterface> $entries
     * @return list<TimeZoneEntryInterface>
     *
     * @throws TranslationException
     */
    private function sortByOffset(
        array $entries,
        LocaleInterface $locale,
    ): array {
        \usort(
            $entries,
            fn (TimeZoneEntryInterface $first, TimeZoneEntryInterface $second): int => $this->compareByOffset(
                firstOffset: $first->offset,
                firstName: $first->displayName,
                secondOffset: $second->offset,
                secondName: $second->displayName,
                locale: $locale,
            ),
        );

        return $entries;
    }

    /**
     * @throws TranslationException
     */
    private function compareByOffset(
        string $firstOffset,
        string $firstName,
        string $secondOffset,
        string $secondName,
        LocaleInterface $locale,
    ): int {
        $order = self::offsetSeconds($firstOffset) <=> self::offsetSeconds($secondOffset);

        return $order !== 0
            ? $order
            : $this->collator->compare(
                first: $firstName,
                second: $secondName,
                locale: $locale,
            );
    }

    private static function offsetSeconds(
        string $offset,
    ): int {
        $sign = $offset[0] === '-'
            ? -1
            : 1;

        [
            $hours,
            $minutes,
        ] = \explode(':', \substr($offset, 1));

        return $sign * (((int) $hours * 3600) + ((int) $minutes * 60));
    }

    private static function lookup(
        \ResourceBundle $bundle,
        string ...$path,
    ): mixed {
        $current = $bundle;

        foreach ($path as $segment) {
            if (!$current instanceof \ResourceBundle) {
                return null;
            }

            $current = $current->get($segment);
        }

        return $current;
    }

    /**
     * @throws TranslationException
     */
    private function zoneStrings(
        LocaleInterface $locale,
    ): \ResourceBundle {
        if (!isset($this->zoneStrings[$locale->tag])) {
            $bundle = \ResourceBundle::create(\str_replace('-', '_', $locale->tag), 'ICUDATA-zone');

            if (!$bundle instanceof \ResourceBundle) {
                // @codeCoverageIgnoreStart
                throw TranslationException::fromUnavailableTimeZoneData();
                // @codeCoverageIgnoreEnd
            }

            $this->zoneStrings[$locale->tag] = $bundle;
        }

        return $this->zoneStrings[$locale->tag];
    }

    /**
     * @throws TranslationException
     */
    private function metaZones(): \ResourceBundle
    {
        if ($this->metaZones === null) {
            $bundle = \ResourceBundle::create('metaZones', null, false);

            if (!$bundle instanceof \ResourceBundle) {
                // @codeCoverageIgnoreStart
                throw TranslationException::fromUnavailableTimeZoneData();
                // @codeCoverageIgnoreEnd
            }

            $this->metaZones = $bundle;
        }

        return $this->metaZones;
    }
}
