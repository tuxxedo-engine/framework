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

    /**
     * @var array<string, list<string>>
     */
    private array $names = [];

    /**
     * @var array<string, \IntlTimeZone>
     */
    private array $timeZones = [];

    /**
     * @var array<string, string>
     */
    private array $canonicalIds = [];

    /**
     * @var array<string, list<array{0: string, 1: string|null, 2: string|null}>>
     */
    private array $metazoneHistories = [];

    /**
     * @var array<string, array<string, array{displayName: string, city: string}>>
     */
    private array $localeData = [];

    /**
     * @var array<string, array<string, string>>
     */
    private array $groupNames = [];

    public function __construct(
        private readonly CollatorInterface $collator = new Collator(),
    ) {
    }

    public function names(
        ?string $country = null,
    ): array {
        $key = $country !== null
            ? self::countryCode($country)
            : '';

        if (!isset($this->names[$key])) {
            $names = [];
            $enumeration = \IntlTimeZone::createTimeZoneIDEnumeration(
                \IntlTimeZone::TYPE_CANONICAL_LOCATION,
                $key !== ''
                    ? $key
                    : null,
            );

            /** @var string $id */
            foreach ($enumeration as $id) {
                $names[] = (string) \IntlTimeZone::getIanaID($id);
            }

            \sort($names);

            $this->names[$key] = $names;
        }

        return $this->names[$key];
    }

    public function all(
        LocaleInterface $locale,
        ?string $country = null,
        ?\DateTimeInterface $at = null,
    ): array {
        return $this->sortByOffset(
            entries: $this->entries(
                names: $this->names($country),
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
            names: $this->names(),
            locale: $locale,
            at: $at,
        );

        foreach ($entries as $entry) {
            $key = $entry->metazone !== null
                ? $entry->metazone . '|' . $this->seasonalOffsets($entry->name, $at)
                : 'zone|' . $entry->name;

            $buckets[$key][] = $entry;
        }

        $readerZones = $locale->region !== null && \preg_match('/^[A-Z]{2}$/', $locale->region) === 1
            ? $this->names($locale->region)
            : [];

        $groups = [];

        foreach ($buckets as $members) {
            $representative = $this->representative($members);
            $ordered = $this->orderMembers(
                entries: $members,
                representative: $representative,
                readerZones: $readerZones,
                locale: $locale,
            );

            $groups[] = new TimeZoneGroup(
                name: $representative->name,
                displayName: $this->groupName(
                    name: $representative->name,
                    locale: $locale,
                ),
                offset: $representative->offset,
                cities: \array_map(
                    static fn (TimeZoneEntryInterface $member): string => $member->city,
                    $ordered,
                ),
                zones: $ordered,
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
     * @throws TranslationException
     */
    private static function countryCode(
        string $country,
    ): string {
        $code = \strtoupper($country);

        if (\preg_match('/^[A-Z]{2}$/', $code) !== 1) {
            throw TranslationException::fromInvalidCountry(
                country: $country,
            );
        }

        return $code;
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
        $canonical = $this->canonicalId($name);
        $data = $this->localeData[$locale->tag][$name] ??= $this->buildLocaleData(
            name: $name,
            canonical: $canonical,
            locale: $locale,
        );

        return new TimeZoneEntry(
            name: $name,
            displayName: $data['displayName'],
            city: $data['city'],
            offset: $this->offsetAt(
                name: $name,
                timestamp: $at->getTimestamp(),
            ),
            region: \str_contains($name, '/')
                ? \explode('/', $name, 2)[0]
                : '',
            metazone: $this->metazone(
                canonical: $canonical,
                at: $at,
            ),
        );
    }

    /**
     * @return array{displayName: string, city: string}
     *
     * @throws TranslationException
     */
    private function buildLocaleData(
        string $name,
        string $canonical,
        LocaleInterface $locale,
    ): array {
        $city = self::lookup(
            $this->zoneStrings($locale),
            'zoneStrings',
            \str_replace('/', ':', $canonical),
            'ec',
        );

        return [
            'displayName' => $this->timeZone($name)->getDisplayName(false, \IntlTimeZone::DISPLAY_GENERIC_LOCATION, $locale->tag),
            'city' => \is_string($city)
                ? $city
                : self::cityFromName($name),
        ];
    }

    /**
     * @throws TranslationException
     */
    private function groupName(
        string $name,
        LocaleInterface $locale,
    ): string {
        return $this->groupNames[$locale->tag][$name] ??= $this->timeZone($name)->getDisplayName(false, \IntlTimeZone::DISPLAY_LONG_GENERIC, $locale->tag);
    }

    /**
     * @throws TranslationException
     */
    private function canonicalId(
        string $name,
    ): string {
        if (!isset($this->canonicalIds[$name])) {
            $canonical = \IntlTimeZone::getCanonicalID($name, $isSystemId);

            if ($isSystemId !== true) {
                throw TranslationException::fromUnknownTimeZone(
                    name: $name,
                );
            }

            $this->canonicalIds[$name] = $canonical;
        }

        return $this->canonicalIds[$name];
    }

    private function timeZone(
        string $name,
    ): \IntlTimeZone {
        return $this->timeZones[$name] ??= \IntlTimeZone::createTimeZone($name);
    }

    private function offsetAt(
        string $name,
        int $timestamp,
    ): string {
        $rawOffset = 0;
        $dstOffset = 0;

        $this->timeZone($name)->getOffset((float) ($timestamp * 1000), false, $rawOffset, $dstOffset);

        /**
         * @var int $rawOffset
         * @var int $dstOffset
         */
        $seconds = \intdiv($rawOffset + $dstOffset, 1000);

        return \sprintf(
            '%s%02d:%02d',
            $seconds < 0
                ? '-'
                : '+',
            \intdiv(\abs($seconds), 3600),
            \intdiv(\abs($seconds) % 3600, 60),
        );
    }

    private function seasonalOffsets(
        string $name,
        \DateTimeInterface $at,
    ): string {
        $year = (int) $at->format('Y');

        return $this->offsetAt($name, (int) \gmmktime(12, 0, 0, 1, 15, $year)) .
            '/' .
            $this->offsetAt($name, (int) \gmmktime(12, 0, 0, 7, 15, $year));
    }

    private static function cityFromName(
        string $name,
    ): string {
        $segments = \explode('/', $name);

        return \str_replace('_', ' ', $segments[\sizeof($segments) - 1]);
    }

    /**
     * @throws TranslationException
     */
    private function metazone(
        string $canonical,
        \DateTimeInterface $at,
    ): ?string {
        $moment = \gmdate('Y-m-d H:i', $at->getTimestamp());

        foreach ($this->metazoneHistory($canonical) as [$metazone, $from, $to]) {
            if ($from === null || $to === null) {
                return $metazone;
            }

            if ($from <= $moment && $moment < $to) {
                return $metazone;
            }
        }

        return null;
    }

    /**
     * @return list<array{0: string, 1: string|null, 2: string|null}>
     *
     * @throws TranslationException
     */
    private function metazoneHistory(
        string $canonical,
    ): array {
        if (!isset($this->metazoneHistories[$canonical])) {
            $history = [];
            $periods = self::lookup(
                $this->metaZones(),
                'metazoneInfo',
                \str_replace('/', ':', $canonical),
            );

            if ($periods instanceof \ResourceBundle) {
                /** @var \ResourceBundle $period */
                foreach ($periods as $period) {
                    /** @var string $metazone */
                    $metazone = $period->get(0);
                    $from = $period->get(1);
                    $to = $period->get(2);

                    $history[] = [
                        $metazone,
                        \is_string($from)
                            ? $from
                            : null,
                        \is_string($to)
                            ? $to
                            : null,
                    ];
                }
            }

            $this->metazoneHistories[$canonical] = $history;
        }

        return $this->metazoneHistories[$canonical];
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
                if ($entry->name === $golden || $this->canonicalId($entry->name) === $golden) {
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
