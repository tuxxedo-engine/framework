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

namespace Unit\Http\Request\Middleware;

use PHPUnit\Framework\TestCase;
use Support\Http\Request\Context\StubBodyContext;
use Support\Http\Request\Context\StubHeaderContext;
use Support\Http\Request\Context\StubInputContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Support\Http\Request\Middleware\RecordingMiddleware;
use Tuxxedo\Http\Request\Middleware\TrustProxy;
use Tuxxedo\Http\Request\Middleware\TrustedHeader;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Net\IpRangeList;
use Tuxxedo\Net\NetException;

class TrustProxyTest extends TestCase
{
    /**
     * @param array<string, string> $headers
     */
    private function makeRequest(
        string $ipAddress = '10.0.0.1',
        array $headers = [],
        string $host = 'origin.example.com',
        int $port = 80,
        bool $https = false,
    ): Request {
        return new Request(
            headers: new StubHeaderContext(
                headers: $headers,
            ),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
            https: $https,
            host: $host,
            port: $port,
            ipAddress: $ipAddress,
        );
    }

    private function lastRequest(
        RecordingMiddleware $next,
    ): RequestInterface {
        self::assertInstanceOf(RequestInterface::class, $next->lastRequest);

        return $next->lastRequest;
    }

    public function testUntrustedUpstreamPassesThroughUnchanged(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '203.0.113.10',
                headers: [
                    'X-Forwarded-For' => '198.51.100.4',
                    'X-Forwarded-Proto' => 'https',
                ],
            ),
            next: $next,
        );

        $request = $this->lastRequest($next);
        self::assertSame('203.0.113.10', $request->ipAddress);
        self::assertFalse($request->https);
    }

    public function testTrustedUpstreamNoForwardedHeadersPassesThroughUnchanged(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
            ),
            next: $next,
        );

        $request = $this->lastRequest($next);
        self::assertSame('10.0.0.5', $request->ipAddress);
    }

    public function testXForwardedForSingleEntryRewritesIpAddress(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Forwarded-For' => '198.51.100.4',
                ],
            ),
            next: $next,
        );

        self::assertSame('198.51.100.4', $this->lastRequest($next)->ipAddress);
    }

    public function testXForwardedForMultiHopReturnsRightmostUntrusted(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Forwarded-For' => '198.51.100.4, 10.0.0.99, 10.0.0.50',
                ],
            ),
            next: $next,
        );

        self::assertSame('198.51.100.4', $this->lastRequest($next)->ipAddress);
    }

    public function testXForwardedForEntireChainTrustedReturnsLeftmost(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Forwarded-For' => '10.0.0.1, 10.0.0.2, 10.0.0.3',
                ],
            ),
            next: $next,
        );

        self::assertSame('10.0.0.1', $this->lastRequest($next)->ipAddress);
    }

    public function testXForwardedForEmptyChainIgnored(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Forwarded-For' => '   ,  ,  ',
                ],
            ),
            next: $next,
        );

        self::assertSame('10.0.0.5', $this->lastRequest($next)->ipAddress);
    }

    public function testXRealIpFallbackWhenXForwardedForAbsent(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Real-IP' => '198.51.100.4',
                ],
            ),
            next: $next,
        );

        self::assertSame('198.51.100.4', $this->lastRequest($next)->ipAddress);
    }

    public function testXRealIpIgnoredWhenEmpty(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Real-IP' => '   ',
                ],
            ),
            next: $next,
        );

        self::assertSame('10.0.0.5', $this->lastRequest($next)->ipAddress);
    }

    public function testXForwardedProtoHttpsRewritesScheme(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Forwarded-Proto' => 'https',
                ],
                https: false,
            ),
            next: $next,
        );

        self::assertTrue($this->lastRequest($next)->https);
    }

    public function testXForwardedProtoHttpRewritesScheme(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Forwarded-Proto' => 'http',
                ],
                https: true,
            ),
            next: $next,
        );

        self::assertFalse($this->lastRequest($next)->https);
    }

    public function testXForwardedHostBareRewritesHost(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Forwarded-Host' => 'public.example.com',
                ],
                port: 80,
            ),
            next: $next,
        );

        $request = $this->lastRequest($next);
        self::assertSame('public.example.com', $request->host);
        self::assertSame(80, $request->port);
    }

    public function testXForwardedHostWithPortRewritesHostAndPort(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Forwarded-Host' => 'public.example.com:8443',
                ],
            ),
            next: $next,
        );

        $request = $this->lastRequest($next);
        self::assertSame('public.example.com', $request->host);
        self::assertSame(8443, $request->port);
    }

    public function testXForwardedHostIpv6BracketedRewritesBoth(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Forwarded-Host' => '[2001:db8::1]:443',
                ],
            ),
            next: $next,
        );

        $request = $this->lastRequest($next);
        self::assertSame('2001:db8::1', $request->host);
        self::assertSame(443, $request->port);
    }

    public function testXForwardedHostIpv6BracketedWithoutPortRewritesHostOnly(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Forwarded-Host' => '[2001:db8::1]',
                ],
                port: 9999,
            ),
            next: $next,
        );

        $request = $this->lastRequest($next);
        self::assertSame('2001:db8::1', $request->host);
        self::assertSame(9999, $request->port);
    }

    public function testXForwardedHostMalformedBracketedIgnored(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Forwarded-Host' => '[unclosed',
                ],
                host: 'origin.example.com',
            ),
            next: $next,
        );

        self::assertSame('origin.example.com', $this->lastRequest($next)->host);
    }

    public function testXForwardedHostBracketedNonNumericPortIgnored(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Forwarded-Host' => '[2001:db8::1]:abc',
                ],
                port: 9999,
            ),
            next: $next,
        );

        $request = $this->lastRequest($next);
        self::assertSame('2001:db8::1', $request->host);
        self::assertSame(9999, $request->port);
    }

    public function testXForwardedHostEmptyValueIgnored(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Forwarded-Host' => '   ',
                ],
                host: 'origin.example.com',
            ),
            next: $next,
        );

        self::assertSame('origin.example.com', $this->lastRequest($next)->host);
    }

    public function testXForwardedHostNonNumericPortIgnored(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Forwarded-Host' => 'public.example.com:abc',
                ],
                port: 9999,
            ),
            next: $next,
        );

        $request = $this->lastRequest($next);
        self::assertSame('public.example.com:abc', $request->host);
        self::assertSame(9999, $request->port);
    }

    public function testXForwardedHostMultipleColonsTreatedAsBare(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Forwarded-Host' => 'foo:bar:8080',
                ],
                port: 9999,
            ),
            next: $next,
        );

        $request = $this->lastRequest($next);
        self::assertSame('foo:bar:8080', $request->host);
        self::assertSame(9999, $request->port);
    }

    public function testXForwardedPortExplicit(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Forwarded-Port' => '9000',
                ],
            ),
            next: $next,
        );

        self::assertSame(9000, $this->lastRequest($next)->port);
    }

    public function testXForwardedPortInvalidIgnored(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Forwarded-Port' => 'abc',
                ],
                port: 9999,
            ),
            next: $next,
        );

        self::assertSame(9999, $this->lastRequest($next)->port);
    }

    public function testXForwardedHostPortRespectsTrustedHeaderPort(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
            trustedHeaders: [
                TrustedHeader::HOST,
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Forwarded-Host' => 'public.example.com:8443',
                ],
                port: 80,
            ),
            next: $next,
        );

        $request = $this->lastRequest($next);
        self::assertSame('public.example.com', $request->host);
        self::assertSame(80, $request->port);
    }

    public function testTrustedHeadersOptOutClientIp(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
            trustedHeaders: [
                TrustedHeader::PROTO,
                TrustedHeader::HOST,
                TrustedHeader::PORT,
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Forwarded-For' => '198.51.100.4',
                ],
            ),
            next: $next,
        );

        self::assertSame('10.0.0.5', $this->lastRequest($next)->ipAddress);
    }

    public function testTrustedHeadersOptOutProto(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
            trustedHeaders: [
                TrustedHeader::CLIENT_IP,
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Forwarded-Proto' => 'https',
                ],
                https: false,
            ),
            next: $next,
        );

        self::assertFalse($this->lastRequest($next)->https);
    }

    public function testTrustedHeadersOptOutHost(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
            trustedHeaders: [
                TrustedHeader::CLIENT_IP,
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Forwarded-Host' => 'public.example.com',
                ],
                host: 'origin.example.com',
            ),
            next: $next,
        );

        self::assertSame('origin.example.com', $this->lastRequest($next)->host);
    }

    public function testTrustedHeadersOptOutPort(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
            trustedHeaders: [
                TrustedHeader::CLIENT_IP,
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Forwarded-Port' => '9000',
                ],
                port: 80,
            ),
            next: $next,
        );

        self::assertSame(80, $this->lastRequest($next)->port);
    }

    public function testForwardedTakesPriorityOverXForwarded(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for=198.51.100.4',
                    'X-Forwarded-For' => '203.0.113.99',
                ],
            ),
            next: $next,
        );

        self::assertSame('198.51.100.4', $this->lastRequest($next)->ipAddress);
    }

    public function testForwardedSimpleClientIp(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for=198.51.100.4',
                ],
            ),
            next: $next,
        );

        self::assertSame('198.51.100.4', $this->lastRequest($next)->ipAddress);
    }

    public function testForwardedProtoAndHost(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for=198.51.100.4;proto=https;host=public.example.com:8443',
                ],
                https: false,
            ),
            next: $next,
        );

        $request = $this->lastRequest($next);
        self::assertTrue($request->https);
        self::assertSame('public.example.com', $request->host);
        self::assertSame(8443, $request->port);
    }

    public function testForwardedIpv6BracketedFor(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for="[2001:db8::1]:443"',
                ],
            ),
            next: $next,
        );

        self::assertSame('2001:db8::1', $this->lastRequest($next)->ipAddress);
    }

    public function testForwardedIpv4WithPort(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for="198.51.100.4:1234"',
                ],
            ),
            next: $next,
        );

        self::assertSame('198.51.100.4', $this->lastRequest($next)->ipAddress);
    }

    public function testForwardedIpv6BracketUnclosedIgnored(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for="[unclosed"',
                ],
            ),
            next: $next,
        );

        self::assertSame('10.0.0.5', $this->lastRequest($next)->ipAddress);
    }

    public function testForwardedObfuscatedForSkipped(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for=_hidden',
                ],
            ),
            next: $next,
        );

        self::assertSame('10.0.0.5', $this->lastRequest($next)->ipAddress);
    }

    public function testForwardedUnknownForSkipped(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for=unknown',
                ],
            ),
            next: $next,
        );

        self::assertSame('10.0.0.5', $this->lastRequest($next)->ipAddress);
    }

    public function testForwardedMultiHopChainWalk(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for=198.51.100.4, for=10.0.0.99, for=10.0.0.50',
                ],
            ),
            next: $next,
        );

        self::assertSame('198.51.100.4', $this->lastRequest($next)->ipAddress);
    }

    public function testForwardedEntireChainTrustedReturnsLeftmost(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for=10.0.0.1, for=10.0.0.2, for=10.0.0.3',
                ],
            ),
            next: $next,
        );

        self::assertSame('10.0.0.1', $this->lastRequest($next)->ipAddress);
    }

    public function testForwardedEntryWithoutForFieldSkippedInWalk(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for=198.51.100.4, proto=https',
                ],
            ),
            next: $next,
        );

        self::assertSame('198.51.100.4', $this->lastRequest($next)->ipAddress);
    }

    public function testForwardedEntryWithInvalidForSkippedInWalk(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for=198.51.100.4, for=_hidden',
                ],
            ),
            next: $next,
        );

        self::assertSame('198.51.100.4', $this->lastRequest($next)->ipAddress);
    }

    public function testForwardedEmptyHeaderIgnored(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => '',
                ],
            ),
            next: $next,
        );

        self::assertSame('10.0.0.5', $this->lastRequest($next)->ipAddress);
    }

    public function testForwardedMalformedEntriesMidChainSkipped(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for=198.51.100.4,  ,for=10.0.0.99',
                ],
            ),
            next: $next,
        );

        self::assertSame('198.51.100.4', $this->lastRequest($next)->ipAddress);
    }

    public function testForwardedPairWithoutEqualsIgnored(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for=198.51.100.4;notapair',
                ],
            ),
            next: $next,
        );

        self::assertSame('198.51.100.4', $this->lastRequest($next)->ipAddress);
    }

    public function testForwardedPairWithEmptyKeyIgnored(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for=198.51.100.4;=value',
                ],
            ),
            next: $next,
        );

        self::assertSame('198.51.100.4', $this->lastRequest($next)->ipAddress);
    }

    public function testForwardedEntryWithOnlyMalformedPairsSkipped(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for=198.51.100.4, notapair',
                ],
            ),
            next: $next,
        );

        self::assertSame('198.51.100.4', $this->lastRequest($next)->ipAddress);
    }

    public function testForwardedNoForFieldAnywhere(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'proto=https',
                ],
                https: false,
            ),
            next: $next,
        );

        $request = $this->lastRequest($next);
        self::assertSame('10.0.0.5', $request->ipAddress);
        self::assertTrue($request->https);
    }

    public function testForwardedHostEmptyValueIgnored(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for=198.51.100.4;host=""',
                ],
                host: 'origin.example.com',
            ),
            next: $next,
        );

        self::assertSame('origin.example.com', $this->lastRequest($next)->host);
    }

    public function testForwardedHostPortRespectsTrustedHeaderPort(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
            trustedHeaders: [
                TrustedHeader::CLIENT_IP,
                TrustedHeader::HOST,
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for=198.51.100.4;host="public.example.com:8443"',
                ],
                port: 80,
            ),
            next: $next,
        );

        $request = $this->lastRequest($next);
        self::assertSame('public.example.com', $request->host);
        self::assertSame(80, $request->port);
    }

    public function testForwardedTrustedHeadersOptOutClientIp(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
            trustedHeaders: [
                TrustedHeader::PROTO,
                TrustedHeader::HOST,
                TrustedHeader::PORT,
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for=198.51.100.4',
                ],
            ),
            next: $next,
        );

        self::assertSame('10.0.0.5', $this->lastRequest($next)->ipAddress);
    }

    public function testForwardedTrustedHeadersOptOutProto(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
            trustedHeaders: [
                TrustedHeader::CLIENT_IP,
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for=198.51.100.4;proto=https',
                ],
                https: false,
            ),
            next: $next,
        );

        self::assertFalse($this->lastRequest($next)->https);
    }

    public function testForwardedTrustedHeadersOptOutHost(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
            trustedHeaders: [
                TrustedHeader::CLIENT_IP,
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for=198.51.100.4;host=public.example.com',
                ],
                host: 'origin.example.com',
            ),
            next: $next,
        );

        self::assertSame('origin.example.com', $this->lastRequest($next)->host);
    }

    public function testForwardedQuotedValueWithEscapedQuote(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for="198.51.100.4";host="ex\\"ample.com"',
                ],
            ),
            next: $next,
        );

        self::assertSame('ex"ample.com', $this->lastRequest($next)->host);
    }

    public function testForwardedQuotedValueRespectsSeparatorsInsideQuote(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for=198.51.100.4;host="weird,host;with;separators"',
                ],
            ),
            next: $next,
        );

        $request = $this->lastRequest($next);
        self::assertSame('198.51.100.4', $request->ipAddress);
        self::assertSame('weird,host;with;separators', $request->host);
    }

    public function testForwardedUnquotedShortValue(): void
    {
        $next = new RecordingMiddleware();

        (new TrustProxy(
            trustedProxies: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'Forwarded' => 'for=a',
                ],
            ),
            next: $next,
        );

        self::assertSame('10.0.0.5', $this->lastRequest($next)->ipAddress);
    }

    public function testConstructorPropagatesNetExceptionOnInvalidProxy(): void
    {
        self::expectException(NetException::class);

        new TrustProxy(
            trustedProxies: [
                'not-an-ip/24',
            ],
        );
    }

    public function testConstructorAcceptsPreBuiltIpRangeList(): void
    {
        $next = new RecordingMiddleware();
        $ranges = new IpRangeList(
            entries: [
                '10.0.0.0/8',
            ],
        );

        (new TrustProxy(
            trustedProxies: $ranges,
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.0.5',
                headers: [
                    'X-Forwarded-For' => '198.51.100.4',
                ],
            ),
            next: $next,
        );

        self::assertSame('198.51.100.4', $this->lastRequest($next)->ipAddress);
    }
}
