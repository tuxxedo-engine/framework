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

namespace Tuxxedo\Http\Url;

class Url implements UrlInterface
{
    /**
     * @throws UrlException
     */
    final public function __construct(
        public readonly string $scheme = '',
        public readonly string $user = '',
        public readonly string $password = '',
        public readonly string $host = '',
        public readonly ?int $port = null,
        public readonly string $path = '',
        public readonly string $query = '',
        public readonly string $fragment = '',
    ) {
        self::guardScheme($scheme);
        self::guardHost($host);
        self::guardPort($port);
    }

    /**
     * @param array<string, scalar|\Stringable|\BackedEnum|array<array-key, scalar|\Stringable|\BackedEnum>|null> $parameters
     *
     * @throws UrlException
     */
    public static function template(
        string $template,
        array $parameters,
    ): static {
        return static::parse(
            (string) \preg_replace_callback(
                '/\{([^{}]*)}/',
                static fn (array $matches): string => self::expand($matches[1], $parameters),
                $template,
            ),
        );
    }

    /**
     * @throws UrlException
     */
    public static function parse(
        string $url,
    ): static {
        if (\preg_match('/[\x00-\x20\x7F]/', $url) === 1) {
            throw UrlException::fromInvalidCharacter($url);
        }

        [$rest, $query, $fragment] = self::splitReference($url);

        $scheme = '';

        if (\preg_match('/^([A-Za-z][A-Za-z0-9+.\-]*):/', $rest, $matches) === 1) {
            $scheme = \strtolower($matches[1]);
            $rest = \substr($rest, \strlen($matches[0]));
        }

        $user = '';
        $password = '';
        $host = '';
        $port = null;

        if (\str_starts_with($rest, '//')) {
            $rest = \substr($rest, 2);
            $length = \strcspn($rest, '/');
            $authority = \substr($rest, 0, $length);
            $rest = \substr($rest, $length);
            $at = \strrpos($authority, '@');

            if ($at !== false) {
                $userInfo = \explode(':', \substr($authority, 0, $at), 2);
                $user = $userInfo[0];
                $password = $userInfo[1] ?? '';
                $authority = \substr($authority, $at + 1);
            }

            [$host, $port] = self::splitHostPort($authority);
        }

        return new static(
            scheme: $scheme,
            user: $user,
            password: $password,
            host: \strtolower($host),
            port: $port,
            path: $rest,
            query: $query,
            fragment: $fragment,
        );
    }

    public function withScheme(
        string $scheme,
    ): static {
        $scheme = \strtolower($scheme);

        self::guardScheme($scheme);

        return clone (
            $this,
            [
                'scheme' => $scheme,
            ],
        );
    }

    public function withUserInfo(
        string $user,
        string $password = '',
    ): static {
        return clone (
            $this,
            [
                'user' => $user,
                'password' => $password,
            ],
        );
    }

    public function withHost(
        string $host,
    ): static {
        $host = \strtolower($host);

        self::guardHost($host);

        return clone (
            $this,
            [
                'host' => $host,
            ],
        );
    }

    public function withPort(
        ?int $port,
    ): static {
        self::guardPort($port);

        return clone (
            $this,
            [
                'port' => $port,
            ],
        );
    }

    public function withPath(
        string $path,
    ): static {
        return clone (
            $this,
            [
                'path' => $path,
            ],
        );
    }

    public function withQuery(
        array|string $query,
    ): static {
        return clone (
            $this,
            [
                'query' => \is_array($query)
                    ? \http_build_query($query, '', '&', \PHP_QUERY_RFC3986)
                    : $query,
            ],
        );
    }

    public function withFragment(
        string $fragment,
    ): static {
        return clone (
            $this,
            [
                'fragment' => $fragment,
            ],
        );
    }

    public function resolve(
        UrlInterface|string $reference,
    ): static {
        if (\is_string($reference)) {
            $reference = self::parse($reference);
        }

        if ($reference->scheme !== '') {
            return new static(
                scheme: $reference->scheme,
                user: $reference->user,
                password: $reference->password,
                host: $reference->host,
                port: $reference->port,
                path: self::removeDotSegments($reference->path),
                query: $reference->query,
                fragment: $reference->fragment,
            );
        }

        if (self::hasAuthority($reference)) {
            return new static(
                scheme: $this->scheme,
                user: $reference->user,
                password: $reference->password,
                host: $reference->host,
                port: $reference->port,
                path: self::removeDotSegments($reference->path),
                query: $reference->query,
                fragment: $reference->fragment,
            );
        }

        if ($reference->path === '') {
            $path = $this->path;
            $query = $reference->query !== ''
                ? $reference->query
                : $this->query;
        } else {
            $path = \str_starts_with($reference->path, '/')
                ? self::removeDotSegments($reference->path)
                : self::removeDotSegments($this->merge($reference->path));
            $query = $reference->query;
        }

        return new static(
            scheme: $this->scheme,
            user: $this->user,
            password: $this->password,
            host: $this->host,
            port: $this->port,
            path: $path,
            query: $query,
            fragment: $reference->fragment,
        );
    }

    public function append(
        string $path,
    ): static {
        [$appended, $query, $fragment] = self::splitReference($path);

        $base = \str_ends_with($this->path, '/')
            ? $this->path
            : $this->path . '/';

        return clone (
            $this,
            [
                'path' => $base . \ltrim($appended, '/'),
                'query' => $query,
                'fragment' => $fragment,
            ],
        );
    }

    public function __toString(): string
    {
        $url = '';

        if ($this->scheme !== '') {
            $url .= $this->scheme . ':';
        }

        if (self::hasAuthority($this)) {
            $url .= '//';

            if ($this->user !== '') {
                $url .= $this->password !== ''
                    ? $this->user . ':' . $this->password . '@'
                    : $this->user . '@';
            }

            $url .= $this->host;

            if ($this->port !== null) {
                $url .= ':' . $this->port;
            }

            if ($this->path !== '' && !\str_starts_with($this->path, '/')) {
                $url .= '/';
            }
        }

        $url .= $this->path;

        if ($this->query !== '') {
            $url .= '?' . $this->query;
        }

        if ($this->fragment !== '') {
            $url .= '#' . $this->fragment;
        }

        return $url;
    }

    private function merge(
        string $path,
    ): string {
        if (self::hasAuthority($this) && $this->path === '') {
            return '/' . $path;
        }

        $slash = \strrpos($this->path, '/');

        return $slash === false
            ? $path
            : \substr($this->path, 0, $slash + 1) . $path;
    }

    private static function hasAuthority(
        UrlInterface $url,
    ): bool {
        return $url->host !== '' || $url->user !== '' || $url->port !== null;
    }

    /**
     * @return array{string, string, string}
     */
    private static function splitReference(
        string $reference,
    ): array {
        $fragment = '';
        $query = '';
        $hash = \strpos($reference, '#');

        if ($hash !== false) {
            $fragment = \substr($reference, $hash + 1);
            $reference = \substr($reference, 0, $hash);
        }

        $question = \strpos($reference, '?');

        if ($question !== false) {
            $query = \substr($reference, $question + 1);
            $reference = \substr($reference, 0, $question);
        }

        return [
            $reference,
            $query,
            $fragment,
        ];
    }

    /**
     * @return array{string, int|null}
     *
     * @throws UrlException
     */
    private static function splitHostPort(
        string $authority,
    ): array {
        if (\str_starts_with($authority, '[')) {
            $close = \strpos($authority, ']');

            if ($close === false) {
                throw UrlException::fromInvalidHost($authority);
            }

            $host = \substr($authority, 0, $close + 1);
            $rest = \substr($authority, $close + 1);
        } else {
            $colon = \strrpos($authority, ':');
            $host = $colon === false
                ? $authority
                : \substr($authority, 0, $colon);
            $rest = $colon === false
                ? ''
                : \substr($authority, $colon);
        }

        if ($rest === '' || $rest === ':') {
            return [
                $host,
                null,
            ];
        }

        if (!\str_starts_with($rest, ':')) {
            throw UrlException::fromInvalidHost($authority);
        }

        $port = \substr($rest, 1);

        if (\preg_match('/^\d{1,5}$/', $port) !== 1) {
            throw UrlException::fromInvalidPort($port);
        }

        return [
            $host,
            (int) $port,
        ];
    }

    private static function removeDotSegments(
        string $path,
    ): string {
        $output = [];

        while ($path !== '') {
            if (\str_starts_with($path, '../')) {
                $path = \substr($path, 3);
            } elseif (\str_starts_with($path, './')) {
                $path = \substr($path, 2);
            } elseif (\str_starts_with($path, '/./')) {
                $path = \substr($path, 2);
            } elseif ($path === '/.') {
                $path = '/';
            } elseif (\str_starts_with($path, '/../')) {
                $path = \substr($path, 3);

                \array_pop($output);
            } elseif ($path === '/..') {
                $path = '/';

                \array_pop($output);
            } elseif ($path === '.' || $path === '..') {
                $path = '';
            } else {
                $next = \strpos($path, '/', 1);

                if ($next === false) {
                    $output[] = $path;
                    $path = '';
                } else {
                    $output[] = \substr($path, 0, $next);
                    $path = \substr($path, $next);
                }
            }
        }

        return \join('', $output);
    }

    /**
     * @throws UrlException
     */
    private static function guardScheme(
        string $scheme,
    ): void {
        if ($scheme !== '' && \preg_match('/^[A-Za-z][A-Za-z0-9+.\-]*$/', $scheme) !== 1) {
            throw UrlException::fromInvalidScheme($scheme);
        }
    }

    /**
     * @throws UrlException
     */
    private static function guardHost(
        string $host,
    ): void {
        if ($host === '') {
            return;
        }

        if (\str_starts_with($host, '[')) {
            if (
                !\str_ends_with($host, ']') ||
                \filter_var(\substr($host, 1, -1), \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6) === false
            ) {
                throw UrlException::fromInvalidHost($host);
            }

            return;
        }

        if (\preg_match('/^[\p{L}\p{N}._~%!$&\'()*+,;=\-]+$/u', $host) !== 1) {
            throw UrlException::fromInvalidHost($host);
        }
    }

    /**
     * @throws UrlException
     */
    private static function guardPort(
        ?int $port,
    ): void {
        if ($port !== null && ($port < 0 || $port > 65535)) {
            throw UrlException::fromInvalidPort((string) $port);
        }
    }

    /**
     * @param array<string, scalar|\Stringable|\BackedEnum|array<array-key, scalar|\Stringable|\BackedEnum>|null> $parameters
     */
    private static function expand(
        string $expression,
        array $parameters,
    ): string {
        $operator = $expression !== '' && \str_contains('+#./;?&', $expression[0])
            ? $expression[0]
            : '';

        [$first, $separator, $named, $ifEmpty, $reserved] = match ($operator) {
            '+' => [
                '',
                ',',
                false,
                '',
                true,
            ],
            '#' => [
                '#',
                ',',
                false,
                '',
                true,
            ],
            '.' => [
                '.',
                '.',
                false,
                '',
                false,
            ],
            '/' => [
                '/',
                '/',
                false,
                '',
                false,
            ],
            ';' => [
                ';',
                ';',
                true,
                '',
                false,
            ],
            '?' => [
                '?',
                '&',
                true,
                '=',
                false,
            ],
            '&' => [
                '&',
                '&',
                true,
                '=',
                false,
            ],
            default => [
                '',
                ',',
                false,
                '',
                false,
            ],
        };

        $parts = [];

        foreach (\explode(',', \substr($expression, \strlen($operator))) as $specification) {
            $prefix = null;
            $explode = \str_ends_with($specification, '*');
            $name = $explode
                ? \substr($specification, 0, -1)
                : $specification;

            if (\str_contains($name, ':')) {
                [$name, $length] = \explode(':', $name, 2);
                $prefix = (int) $length;
            }

            $value = $parameters[$name] ?? null;

            if ($value === null || $value === []) {
                continue;
            }

            if (!\is_array($value)) {
                $string = self::templateValue($value);

                if ($prefix !== null && \preg_match('/^.{0,' . $prefix . '}/su', $string, $matches) === 1) {
                    $string = $matches[0];
                }

                $encoded = self::templateEncode($string, $reserved);
                $parts[] = match (true) {
                    !$named => $encoded,
                    $encoded === '' => $name . $ifEmpty,
                    default => $name . '=' . $encoded,
                };

                continue;
            }

            $list = \array_is_list($value);

            if (!$explode) {
                $items = [];

                foreach ($value as $key => $item) {
                    if (!$list) {
                        $items[] = self::templateEncode((string) $key, $reserved);
                    }

                    $items[] = self::templateEncode(self::templateValue($item), $reserved);
                }

                $parts[] = $named
                    ? $name . '=' . \join(',', $items)
                    : \join(',', $items);

                continue;
            }

            foreach ($value as $key => $item) {
                $encoded = self::templateEncode(self::templateValue($item), $reserved);
                $label = $list
                    ? $name
                    : self::templateEncode((string) $key, $reserved);

                $parts[] = match (true) {
                    $list && !$named => $encoded,
                    $encoded === '' && $named => $label . $ifEmpty,
                    default => $label . '=' . $encoded,
                };
            }
        }

        return \sizeof($parts) > 0
            ? $first . \join($separator, $parts)
            : '';
    }

    private static function templateValue(
        string|int|float|bool|\Stringable|\BackedEnum $value,
    ): string {
        return match (true) {
            $value instanceof \BackedEnum => (string) $value->value,
            $value === true => '1',
            $value === false => '0',
            default => (string) $value,
        };
    }

    private static function templateEncode(
        string $value,
        bool $reserved,
    ): string {
        if (!$reserved) {
            return \rawurlencode($value);
        }

        return (string) \preg_replace_callback(
            '/%[0-9A-Fa-f]{2}|[^A-Za-z0-9\-._~:\/?#\[\]@!$&\'()*+,;=]/',
            static fn (array $matches): string => \strlen($matches[0]) === 3
                ? $matches[0]
                : \rawurlencode($matches[0]),
            $value,
        );
    }
}
