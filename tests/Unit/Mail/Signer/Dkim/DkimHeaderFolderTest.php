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

namespace Unit\Mail\Signer\Dkim;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Mail\Signer\Dkim\DkimHeaderFolder;

class DkimHeaderFolderTest extends TestCase
{
    public function testHeaderWithoutColonIsReturnedUnchanged(): void
    {
        self::assertSame(
            'no colon here',
            DkimHeaderFolder::fold(
                headerLine: 'no colon here',
            ),
        );
    }

    public function testShortHeaderFitsOnASingleLine(): void
    {
        $line = 'DKIM-Signature: v=1; a=rsa-sha256; d=example.com; s=default';

        self::assertSame(
            $line,
            DkimHeaderFolder::fold(
                headerLine: $line,
            ),
        );
    }

    public function testMultipleTagsPackAcrossLinesWhenExceedingLineLimit(): void
    {
        $line = 'DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed; d=example.com; s=default; bh=hash; b=sig';

        self::assertSame(
            "DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed; d=example.com;\r\n\ts=default; bh=hash; b=sig",
            DkimHeaderFolder::fold(
                headerLine: $line,
            ),
        );
    }

    public function testSingleTagLongerThanLimitIsChunkedAt74Chars(): void
    {
        $line = 'K: ' . \str_repeat('A', 80);

        $expected = "K:\r\n\t" . \str_repeat('A', 73) . "\r\n " . \str_repeat('A', 7);

        self::assertSame(
            $expected,
            DkimHeaderFolder::fold(
                headerLine: $line,
            ),
        );
    }

    public function testMultipleTagsWhereFirstAlonePushesPastLimitStartsWithHeaderOnly(): void
    {
        $bigTag = 'x=' . \str_repeat('B', 90);
        $line = 'DKIM-Signature: ' . $bigTag . '; b=sig';

        $result = DkimHeaderFolder::fold(
            headerLine: $line,
        );

        self::assertStringStartsWith("DKIM-Signature:\r\n\t", $result);
        self::assertStringEndsWith('b=sig', $result);
    }
}
