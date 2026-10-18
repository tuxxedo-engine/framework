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

namespace Unit\Intl\Temporal;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\Temporal\TimeZoneEntryInterface;
use Tuxxedo\Intl\Temporal\TimeZoneGroupInterface;
use Tuxxedo\Intl\Temporal\TimeZoneList;
use Tuxxedo\Intl\TranslationException;

class TimeZoneListTest extends TestCase
{
    private function january(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-01-15 12:00:00', new \DateTimeZone('UTC'));
    }

    /**
     * @param list<TimeZoneGroupInterface> $groups
     */
    private function groupOf(
        array $groups,
        string $zone,
    ): TimeZoneGroupInterface {
        foreach ($groups as $group) {
            foreach ($group->zones as $member) {
                if ($member->name === $zone) {
                    return $group;
                }
            }
        }

        self::fail('No group contains ' . $zone);
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

    public function testEntryCarriesLocalizedNamesOffsetRegionAndMetazone(): void
    {
        $entry = (new TimeZoneList())->of(
            names: [
                'Europe/Copenhagen',
            ],
            locale: Locale::parse('da-DK'),
            at: $this->january(),
        )[0];

        self::assertSame('Europe/Copenhagen', $entry->name);
        self::assertSame('Danmark-tid', $entry->displayName);
        self::assertSame('København', $entry->city);
        self::assertSame('+01:00', $entry->offset);
        self::assertSame('Europe', $entry->region);
        self::assertSame('Europe_Central', $entry->metazone);
    }

    public function testCityFallsBackToTheIdentifierWhenIcuHasNoExemplarCity(): void
    {
        $entry = (new TimeZoneList())->of(
            names: [
                'America/Sao_Paulo',
            ],
            locale: Locale::parse('en-US'),
            at: $this->january(),
        )[0];

        self::assertSame('Sao Paulo', $entry->city);
        self::assertSame('Brasilia', $entry->metazone);
    }

    public function testLookupsUseTheIcuCanonicalIdentifier(): void
    {
        $entry = (new TimeZoneList())->of(
            names: [
                'Asia/Kolkata',
            ],
            locale: Locale::parse('en-US'),
            at: $this->january(),
        )[0];

        self::assertSame('India', $entry->metazone);
        self::assertSame('+05:30', $entry->offset);
    }

    public function testOffsetsKeepNonHourValues(): void
    {
        $entries = (new TimeZoneList())->of(
            names: [
                'Pacific/Chatham',
                'Australia/Lord_Howe',
            ],
            locale: Locale::parse('en-US'),
            at: $this->january(),
        );

        self::assertSame('+13:45', $entries[0]->offset);
        self::assertSame('+11:00', $entries[1]->offset);
    }

    public function testOfRejectsZonesUnknownToIcu(): void
    {
        try {
            (new TimeZoneList())->of(
                names: [
                    '+02:00',
                ],
                locale: Locale::parse('en-US'),
            );

            self::fail('Expected TranslationException');
        } catch (TranslationException $exception) {
            self::assertStringContainsString(
                'Unknown time zone',
                $exception->getMessage(),
            );
        }
    }

    public function testNamesAreIcuLocationZonesWithIanaIdentifiers(): void
    {
        $list = new TimeZoneList();
        $names = $list->names();

        self::assertContains('Asia/Kolkata', $names);
        self::assertContains('Europe/Kyiv', $names);
        self::assertNotContains('Asia/Calcutta', $names);
        self::assertNotContains('UTC', $names);
        self::assertSame(
            [
                'Europe/Copenhagen',
            ],
            $list->names('dk'),
        );
    }

    public function testNamesRejectMalformedCountry(): void
    {
        $this->expectException(TranslationException::class);

        (new TimeZoneList())->names('Denmark');
    }

    public function testRepeatedCallsReturnTheSameEntries(): void
    {
        $list = new TimeZoneList();
        $locale = Locale::parse('da-DK');

        self::assertEquals(
            $list->all(
                locale: $locale,
                at: $this->january(),
            ),
            $list->all(
                locale: $locale,
                at: $this->january(),
            ),
        );
    }

    public function testOfKeepsTheGivenOrder(): void
    {
        $names = \array_map(
            static fn (TimeZoneEntryInterface $entry): string => $entry->name,
            (new TimeZoneList())->of(
                names: [
                    'Europe/Lisbon',
                    'America/New_York',
                    'Asia/Tokyo',
                ],
                locale: Locale::parse('en-US'),
            ),
        );

        self::assertSame(
            [
                'Europe/Lisbon',
                'America/New_York',
                'Asia/Tokyo',
            ],
            $names,
        );
    }

    public function testOfRejectsUnknownTimeZone(): void
    {
        try {
            (new TimeZoneList())->of(
                names: [
                    'Mars/Olympus_Mons',
                ],
                locale: Locale::parse('en-US'),
            );

            self::fail('Expected TranslationException');
        } catch (TranslationException $exception) {
            self::assertStringContainsString(
                'Unknown time zone',
                $exception->getMessage(),
            );
        }
    }

    public function testAllFiltersByCountryAndSortsByOffset(): void
    {
        $entries = (new TimeZoneList())->all(
            locale: Locale::parse('en-US'),
            country: 'pt',
            at: $this->january(),
        );

        $names = \array_map(
            static fn (TimeZoneEntryInterface $entry): string => $entry->name,
            $entries,
        );

        self::assertEqualsCanonicalizing(
            [
                'Atlantic/Azores',
                'Atlantic/Madeira',
                'Europe/Lisbon',
            ],
            $names,
        );

        self::assertSame('Atlantic/Azores', $entries[0]->name);
        self::assertSame('-01:00', $entries[0]->offset);
    }

    public function testAllListsEveryZoneFromNegativeToPositiveOffset(): void
    {
        $entries = (new TimeZoneList())->all(
            locale: Locale::parse('en-US'),
            at: $this->january(),
        );

        self::assertSame(\sizeof((new TimeZoneList())->names()), \sizeof($entries));

        for ($i = 1, $size = \sizeof($entries); $i < $size; $i++) {
            self::assertLessThanOrEqual(
                self::offsetSeconds($entries[$i]->offset),
                self::offsetSeconds($entries[$i - 1]->offset),
            );
        }
    }

    public function testAllRejectsMalformedCountry(): void
    {
        try {
            (new TimeZoneList())->all(
                locale: Locale::parse('en-US'),
                country: 'Denmark',
            );

            self::fail('Expected TranslationException');
        } catch (TranslationException $exception) {
            self::assertStringContainsString(
                'two-letter ISO 3166-1 code',
                $exception->getMessage(),
            );
        }
    }

    public function testGroupsPutTheReadersCountryFirstThenTheRepresentative(): void
    {
        $group = $this->groupOf(
            groups: (new TimeZoneList())->grouped(
                locale: Locale::parse('da-DK'),
                at: $this->january(),
            ),
            zone: 'Europe/Stockholm',
        );

        self::assertSame('Europe/Paris', $group->name);
        self::assertSame('Centraleuropæisk tid', $group->displayName);
        self::assertSame('+01:00', $group->offset);
        self::assertSame('København', $group->cities[0]);
        self::assertSame('Paris', $group->cities[1]);
        self::assertSame(\sizeof($group->zones), \sizeof($group->cities));
    }

    public function testGroupsStartWithTheRepresentativeWithoutReaderCountry(): void
    {
        $group = $this->groupOf(
            groups: (new TimeZoneList())->grouped(
                locale: Locale::parse('da'),
                at: $this->january(),
            ),
            zone: 'Europe/Copenhagen',
        );

        self::assertSame('Paris', $group->cities[0]);
    }

    public function testGroupsSplitZonesWithDifferentDaylightSavingRules(): void
    {
        $groups = (new TimeZoneList())->grouped(
            locale: Locale::parse('en-US'),
            at: $this->january(),
        );

        self::assertNotSame(
            $this->groupOf($groups, 'America/Denver')->name,
            $this->groupOf($groups, 'America/Phoenix')->name,
        );

        for ($i = 1, $size = \sizeof($groups); $i < $size; $i++) {
            self::assertLessThanOrEqual(
                self::offsetSeconds($groups[$i]->offset),
                self::offsetSeconds($groups[$i - 1]->offset),
            );
        }
    }
}
