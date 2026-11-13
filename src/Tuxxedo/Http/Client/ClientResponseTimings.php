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

namespace Tuxxedo\Http\Client;

use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\DurationInterface;

readonly class ClientResponseTimings implements ClientResponseTimingsInterface
{
    public function __construct(
        public DurationInterface $dns,
        public DurationInterface $connect,
        public DurationInterface $tls,
        public DurationInterface $waiting,
        public DurationInterface $download,
        public DurationInterface $total,
    ) {
    }

    /**
     * @param array<string, mixed> $info
     */
    public static function fromInfo(
        array $info,
        ?int $totalMicroseconds = null,
    ): self {
        $dns = self::microseconds($info, 'namelookup_time_us');
        $connect = self::microseconds($info, 'connect_time_us');
        $tls = self::microseconds($info, 'appconnect_time_us');
        $pretransfer = self::microseconds($info, 'pretransfer_time_us');
        $firstByte = self::microseconds($info, 'starttransfer_time_us');
        $total = self::microseconds($info, 'total_time_us');

        return new self(
            dns: self::duration($dns),
            connect: self::duration($connect - $dns),
            tls: self::duration(
                $tls > 0
                    ? $tls - $connect
                    : 0,
            ),
            waiting: self::duration($firstByte - $pretransfer),
            download: self::duration($total - $firstByte),
            total: self::duration($totalMicroseconds ?? $total),
        );
    }

    /**
     * @param array<string, mixed> $info
     */
    private static function microseconds(
        array $info,
        string $key,
    ): int {
        $value = $info[$key] ?? 0;

        return \is_int($value)
            ? $value
            : 0;
    }

    private static function duration(
        int $microseconds,
    ): DurationInterface {
        return Duration::fromMicroseconds(\max(0, $microseconds));
    }
}
