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

namespace Unit\Net;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Net\IpRangeList;
use Tuxxedo\Net\NetException;

class IpRangeListTest extends TestCase
{
    public function testContainsIpv4LiteralMatch(): void
    {
        $list = new IpRangeList(
            entries: [
                '127.0.0.1',
            ],
        );

        self::assertTrue(
            $list->contains(
                ip: '127.0.0.1',
            ),
        );
    }

    public function testContainsIpv6LiteralMatch(): void
    {
        $list = new IpRangeList(
            entries: [
                '::1',
            ],
        );

        self::assertTrue(
            $list->contains(
                ip: '::1',
            ),
        );
    }

    public function testContainsReturnsFalseForNonMatchingIp(): void
    {
        $list = new IpRangeList(
            entries: [
                '10.0.0.1',
            ],
        );

        self::assertFalse(
            $list->contains(
                ip: '127.0.0.1',
            ),
        );
    }

    public function testContainsIpv4WithinCidr(): void
    {
        $list = new IpRangeList(
            entries: [
                '10.0.0.0/8',
            ],
        );

        self::assertTrue(
            $list->contains(
                ip: '10.5.5.5',
            ),
        );
    }

    public function testContainsRejectsIpv4OutsideCidr(): void
    {
        $list = new IpRangeList(
            entries: [
                '10.0.0.0/8',
            ],
        );

        self::assertFalse(
            $list->contains(
                ip: '11.0.0.1',
            ),
        );
    }

    public function testContainsCidrWithNonByteAlignedPrefix(): void
    {
        $list = new IpRangeList(
            entries: [
                '10.0.0.0/23',
            ],
        );

        self::assertTrue(
            $list->contains(
                ip: '10.0.1.5',
            ),
        );
    }

    public function testContainsCidrAtMaxPrefix(): void
    {
        $list = new IpRangeList(
            entries: [
                '127.0.0.1/32',
            ],
        );

        self::assertTrue(
            $list->contains(
                ip: '127.0.0.1',
            ),
        );
    }

    public function testContainsIpv6Cidr(): void
    {
        $list = new IpRangeList(
            entries: [
                '2001:db8::/32',
            ],
        );

        self::assertTrue(
            $list->contains(
                ip: '2001:db8:abcd::1',
            ),
        );
    }

    public function testContainsRejectsCrossFamilyMatch(): void
    {
        $list = new IpRangeList(
            entries: [
                '::1',
            ],
        );

        self::assertFalse(
            $list->contains(
                ip: '127.0.0.1',
            ),
        );
    }

    public function testContainsReturnsFalseForUnparsableIp(): void
    {
        $list = new IpRangeList(
            entries: [
                '10.0.0.0/8',
            ],
        );

        self::assertFalse(
            $list->contains(
                ip: 'not-an-ip',
            ),
        );
    }

    public function testContainsResolvedHostname(): void
    {
        $list = new IpRangeList(
            entries: [
                'localhost',
            ],
        );

        self::assertTrue(
            $list->contains(
                ip: '127.0.0.1',
            ),
        );
    }

    public function testEmptyListMatchesNothing(): void
    {
        $list = new IpRangeList(
            entries: [],
        );

        self::assertFalse(
            $list->contains(
                ip: '127.0.0.1',
            ),
        );
    }

    public function testThrowsOnUnresolvableHostname(): void
    {
        self::expectException(NetException::class);

        new IpRangeList(
            entries: [
                'nonexistent-tuxxedo-iprangelist.invalid',
            ],
        );
    }

    public function testThrowsOnCidrWithInvalidIp(): void
    {
        self::expectException(NetException::class);

        new IpRangeList(
            entries: [
                'not-an-ip/24',
            ],
        );
    }

    public function testThrowsOnCidrWithNonNumericPrefix(): void
    {
        self::expectException(NetException::class);

        new IpRangeList(
            entries: [
                '10.0.0.0/abc',
            ],
        );
    }

    public function testThrowsOnCidrPrefixOutOfRange(): void
    {
        self::expectException(NetException::class);

        new IpRangeList(
            entries: [
                '10.0.0.0/64',
            ],
        );
    }
}
