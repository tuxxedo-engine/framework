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

namespace Tuxxedo\Http\Client\Response;

use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Url\Url;
use Tuxxedo\Http\Url\UrlInterface;

class ClientResponseInfo implements ClientResponseInfoInterface
{
    private ?UrlInterface $effectiveUrlCache = null;
    private ?ClientResponseTimingsInterface $timingsCache = null;

    /**
     * @var list<ClientResponseHopInterface>|null
     */
    private ?array $hopsCache = null;

    public UrlInterface $effectiveUrl {
        get {
            return $this->effectiveUrlCache ??= Url::parse(self::string($this->info, 'url'));
        }
    }

    public ?string $primaryIp {
        get {
            $primaryIp = self::string($this->info, 'primary_ip');

            return $primaryIp !== ''
                ? $primaryIp
                : null;
        }
    }

    public ?int $primaryPort {
        get {
            $primaryPort = self::int($this->info, 'primary_port');

            return $primaryPort > 0
                ? $primaryPort
                : null;
        }
    }

    public bool $reusedConnection {
        get {
            return self::int($this->info, 'num_connects') === 0;
        }
    }

    public int $uploadedBytes {
        get {
            return self::int($this->info, 'size_upload');
        }
    }

    public int $downloadedBytes {
        get {
            return self::int($this->info, 'size_download');
        }
    }

    public ClientResponseTimingsInterface $timings {
        get {
            if ($this->timingsCache === null) {
                $total = self::int($this->info, 'total_time_us');

                foreach ($this->previousHops as $hop) {
                    $total += self::int($hop, 'total_time_us');
                }

                $this->timingsCache = ClientResponseTimings::fromInfo(
                    info: $this->info,
                    totalMicroseconds: $total,
                );
            }

            return $this->timingsCache;
        }
    }

    public array $hops {
        get {
            if ($this->hopsCache === null) {
                $this->hopsCache = [];

                foreach ($this->previousHops as $hop) {
                    $statusCode = self::int($hop, 'http_code');
                    $primaryIp = self::string($hop, 'primary_ip');

                    $this->hopsCache[] = new ClientResponseHop(
                        url: Url::parse(self::string($hop, 'url')),
                        responseCode: ResponseCode::resolve($statusCode),
                        statusCode: $statusCode,
                        primaryIp: $primaryIp !== ''
                            ? $primaryIp
                            : null,
                        timings: ClientResponseTimings::fromInfo($hop),
                    );
                }
            }

            return $this->hopsCache;
        }
    }

    public int $redirectCount {
        get {
            return \sizeof($this->previousHops);
        }
    }

    public bool $redirected {
        get {
            return $this->redirectCount > 0;
        }
    }

    /**
     * @param array<string, mixed> $info
     * @param list<array<string, mixed>> $previousHops
     */
    public function __construct(
        private readonly array $info,
        private readonly array $previousHops = [],
    ) {
    }

    /**
     * @param list<ClientResponseInfoInterface> $previous
     */
    public static function chain(
        ClientResponseInfoInterface $final,
        array $previous,
    ): ClientResponseInfoInterface {
        if (!$final instanceof self || \sizeof($previous) === 0) {
            return $final;
        }

        $hops = [];

        foreach ($previous as $hop) {
            if (!$hop instanceof self) {
                return $final;
            }

            $hops[] = $hop->info;
        }

        return new self(
            info: $final->info,
            previousHops: $hops,
        );
    }

    /**
     * @param array<string, mixed> $info
     */
    private static function int(
        array $info,
        string $key,
    ): int {
        $value = $info[$key] ?? 0;

        return match (true) {
            \is_int($value) => $value,
            \is_float($value) => (int) $value,
            default => 0,
        };
    }

    /**
     * @param array<string, mixed> $info
     */
    private static function string(
        array $info,
        string $key,
    ): string {
        $value = $info[$key] ?? '';

        return \is_string($value)
            ? $value
            : '';
    }
}
