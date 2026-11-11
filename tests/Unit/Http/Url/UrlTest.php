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

namespace Unit\Http\Url;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Url\Url;
use Tuxxedo\Http\Url\UrlException;

class UrlTest extends TestCase
{
    public function testParseSplitsEveryPart(): void
    {
        $url = Url::parse('HTTPS://ada:secret@Example.COM:8443/a/b?x=1&y=2#top');

        self::assertSame('https', $url->scheme);
        self::assertSame('ada', $url->user);
        self::assertSame('secret', $url->password);
        self::assertSame('example.com', $url->host);
        self::assertSame(8443, $url->port);
        self::assertSame('/a/b', $url->path);
        self::assertSame('x=1&y=2', $url->query);
        self::assertSame('top', $url->fragment);
        self::assertSame('https://ada:secret@example.com:8443/a/b?x=1&y=2#top', (string) $url);
    }

    public function testParseIpv6Hosts(): void
    {
        $url = Url::parse('http://[::1]:8080/');

        self::assertSame('[::1]', $url->host);
        self::assertSame(8080, $url->port);
        self::assertSame('http://[::1]:8080/', (string) $url);
        self::assertNull(Url::parse('http://[2001:db8::7]')->port);
    }

    public function testParseReferencesWithoutSchemeOrAuthority(): void
    {
        $url = Url::parse('/docs/intro?page=2');

        self::assertSame('', $url->scheme);
        self::assertSame('', $url->host);
        self::assertSame('/docs/intro', $url->path);
        self::assertSame('/docs/intro?page=2', (string) $url);
        self::assertSame('', (string) Url::parse(''));
        self::assertSame('mailto:ada@example.com', (string) Url::parse('mailto:ada@example.com'));
        self::assertNull(Url::parse('http://example.com:')->port);
    }

    public function testToStringAddsTheSlashBetweenAuthorityAndRelativePath(): void
    {
        self::assertSame('http://example.com/docs', (string) Url::parse('http://example.com')->withPath('docs'));
        self::assertSame('//cdn.example/a.js', (string) Url::parse('//cdn.example/a.js'));
        self::assertSame('http://ada@example.com', (string) Url::parse('http://ada@example.com'));
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function invalidUrlProvider(): \Generator
    {
        yield 'whitespace' => [
            'http://exa mple.com/',
            'whitespace or control characters',
        ];

        yield 'control character' => [
            "http://example.com/\x01",
            'whitespace or control characters',
        ];

        yield 'unclosed ipv6' => [
            'http://[::1/',
            'host "[::1"',
        ];

        yield 'invalid ipv6' => [
            'http://[zz::1]/',
            'host "[zz::1]"',
        ];

        yield 'junk after ipv6' => [
            'http://[::1]x/',
            'host "[::1]x"',
        ];

        yield 'invalid host character' => [
            'http://exa"mple.com/',
            'host "exa"mple.com"',
        ];

        yield 'non-numeric port' => [
            'http://example.com:http/',
            'port "http"',
        ];

        yield 'port too large' => [
            'http://example.com:70000/',
            'port "70000"',
        ];
    }

    #[DataProvider('invalidUrlProvider')]
    public function testParseRejectsInvalidUrls(
        string $url,
        string $message,
    ): void {
        try {
            Url::parse($url);

            self::fail('Expected UrlException was not thrown');
        } catch (UrlException $exception) {
            self::assertStringContainsString($message, $exception->getMessage());
        }
    }

    public function testWithers(): void
    {
        $url = Url::parse('http://example.com/a')
            ->withScheme('HTTPS')
            ->withUserInfo('ada', 'secret')
            ->withHost('API.Example.com')
            ->withPort(8443)
            ->withPath('/b')
            ->withQuery(
                [
                    'q' => 'a b',
                    'tags' => [
                        'x',
                    ],
                ],
            )
            ->withFragment('top');

        self::assertSame('https://ada:secret@api.example.com:8443/b?q=a%20b&tags%5B0%5D=x#top', (string) $url);
        self::assertSame('https://api.example.com/b?raw=1', (string) $url->withUserInfo('')->withPort(null)->withQuery('raw=1')->withFragment(''));
    }

    public function testWithersValidate(): void
    {
        $url = Url::parse('http://example.com/');

        foreach (
            [
                static fn (): Url => $url->withScheme('1http'),
                static fn (): Url => $url->withHost('exa mple'),
                static fn (): Url => $url->withPort(-1),
                static fn (): Url => new Url(port: 65536),
            ] as $wither
        ) {
            try {
                $wither();

                self::fail('Expected UrlException was not thrown');
            } catch (UrlException $exception) {
                self::assertStringContainsString('is not valid', $exception->getMessage());
            }
        }
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function resolveProvider(): \Generator
    {
        yield 'normal g:h' => [
            'g:h',
            'g:h',
        ];

        yield 'normal g' => [
            'g',
            'http://a/b/c/g',
        ];

        yield 'normal ./g' => [
            './g',
            'http://a/b/c/g',
        ];

        yield 'normal g/' => [
            'g/',
            'http://a/b/c/g/',
        ];

        yield 'normal /g' => [
            '/g',
            'http://a/g',
        ];

        yield 'normal //g' => [
            '//g',
            'http://g',
        ];

        yield 'normal ?y' => [
            '?y',
            'http://a/b/c/d;p?y',
        ];

        yield 'normal g?y' => [
            'g?y',
            'http://a/b/c/g?y',
        ];

        yield 'normal #s' => [
            '#s',
            'http://a/b/c/d;p?q#s',
        ];

        yield 'normal g#s' => [
            'g#s',
            'http://a/b/c/g#s',
        ];

        yield 'normal g?y#s' => [
            'g?y#s',
            'http://a/b/c/g?y#s',
        ];

        yield 'normal ;x' => [
            ';x',
            'http://a/b/c/;x',
        ];

        yield 'normal g;x' => [
            'g;x',
            'http://a/b/c/g;x',
        ];

        yield 'normal g;x?y#s' => [
            'g;x?y#s',
            'http://a/b/c/g;x?y#s',
        ];

        yield 'normal empty' => [
            '',
            'http://a/b/c/d;p?q',
        ];

        yield 'normal .' => [
            '.',
            'http://a/b/c/',
        ];

        yield 'normal ./' => [
            './',
            'http://a/b/c/',
        ];

        yield 'normal ..' => [
            '..',
            'http://a/b/',
        ];

        yield 'normal ../' => [
            '../',
            'http://a/b/',
        ];

        yield 'normal ../g' => [
            '../g',
            'http://a/b/g',
        ];

        yield 'normal ../..' => [
            '../..',
            'http://a/',
        ];

        yield 'normal ../../' => [
            '../../',
            'http://a/',
        ];

        yield 'normal ../../g' => [
            '../../g',
            'http://a/g',
        ];

        yield 'abnormal ../../../g' => [
            '../../../g',
            'http://a/g',
        ];

        yield 'abnormal ../../../../g' => [
            '../../../../g',
            'http://a/g',
        ];

        yield 'abnormal /./g' => [
            '/./g',
            'http://a/g',
        ];

        yield 'abnormal /../g' => [
            '/../g',
            'http://a/g',
        ];

        yield 'abnormal g.' => [
            'g.',
            'http://a/b/c/g.',
        ];

        yield 'abnormal .g' => [
            '.g',
            'http://a/b/c/.g',
        ];

        yield 'abnormal g..' => [
            'g..',
            'http://a/b/c/g..',
        ];

        yield 'abnormal ..g' => [
            '..g',
            'http://a/b/c/..g',
        ];

        yield 'abnormal ./../g' => [
            './../g',
            'http://a/b/g',
        ];

        yield 'abnormal ./g/.' => [
            './g/.',
            'http://a/b/c/g/',
        ];

        yield 'abnormal g/./h' => [
            'g/./h',
            'http://a/b/c/g/h',
        ];

        yield 'abnormal g/../h' => [
            'g/../h',
            'http://a/b/c/h',
        ];

        yield 'abnormal g;x=1/./y' => [
            'g;x=1/./y',
            'http://a/b/c/g;x=1/y',
        ];

        yield 'abnormal g;x=1/../y' => [
            'g;x=1/../y',
            'http://a/b/c/y',
        ];

        yield 'abnormal g?y/./x' => [
            'g?y/./x',
            'http://a/b/c/g?y/./x',
        ];

        yield 'abnormal g?y/../x' => [
            'g?y/../x',
            'http://a/b/c/g?y/../x',
        ];

        yield 'abnormal g#s/./x' => [
            'g#s/./x',
            'http://a/b/c/g#s/./x',
        ];

        yield 'abnormal g#s/../x' => [
            'g#s/../x',
            'http://a/b/c/g#s/../x',
        ];
    }

    #[DataProvider('resolveProvider')]
    public function testResolveFollowsRfc3986(
        string $reference,
        string $expected,
    ): void {
        self::assertSame($expected, (string) Url::parse('http://a/b/c/d;p?q')->resolve($reference));
    }

    public function testResolveAcceptsUrlInstancesAndBasesWithoutPath(): void
    {
        self::assertSame('http://a/g', (string) Url::parse('http://a')->resolve(Url::parse('g')));
        self::assertSame('https://b/x', (string) Url::parse('http://a/')->resolve(Url::parse('https://b/x')));
        self::assertSame('g', (string) Url::parse('')->resolve('g'));
        self::assertSame('g', (string) Url::parse('')->resolve('../g'));
        self::assertSame('g', (string) Url::parse('')->resolve('./g'));
        self::assertSame('', (string) Url::parse('')->resolve('..'));
        self::assertSame('x:h', (string) Url::parse('http://a/')->resolve('x:./h'));
    }

    public function testAppend(): void
    {
        $url = Url::parse('https://tuxxedo.dev/');

        self::assertSame('https://tuxxedo.dev/', (string) $url->append(''));
        self::assertSame('https://tuxxedo.dev/', (string) $url->append('/'));
        self::assertSame('https://tuxxedo.dev/docs', (string) $url->append('docs'));
        self::assertSame('https://tuxxedo.dev/docs', (string) $url->append('/docs'));
        self::assertSame('https://tuxxedo.dev/download/latest', (string) $url->append('download/latest'));
        self::assertSame('https://tuxxedo.dev/dynamic?page=admin#top', (string) $url->append('dynamic?page=admin#top'));
        self::assertSame('https://shop.example/app/args/x', (string) Url::parse('https://shop.example/app')->append('/args/x'));
        self::assertSame('/view/link', (string) new Url(path: '/')->append('view/link'));
    }
}
