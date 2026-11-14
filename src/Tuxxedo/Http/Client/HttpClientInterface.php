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

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\File\FileException;
use Tuxxedo\File\Storage\StorageException;
use Tuxxedo\File\Storage\StorageInterface;
use Tuxxedo\Http\Client\Auth\AuthInterface;
use Tuxxedo\Http\Client\Body\RequestBodyInterface;
use Tuxxedo\Http\Client\Decoder\ResponseDecoderInterface;
use Tuxxedo\Http\Client\Request\ClientRequestInterface;
use Tuxxedo\Http\Client\Response\ClientResponseInterface;
use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\HttpVersion;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Stream\SseEventInterface;
use Tuxxedo\Http\Stream\StreamException;
use Tuxxedo\Http\Url\UrlException;
use Tuxxedo\Http\Url\UrlInterface;
use Tuxxedo\Serializer\SerializerException;
use Tuxxedo\Temporal\DurationInterface;

#[DefaultImplementation(class: HttpClient::class, lifecycle: Lifecycle::SINGLETON)]
interface HttpClientInterface
{
    /**
     * @template T of object
     *
     * @param class-string<T>|null $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return ($as is null ? ClientResponseInterface : T)
     *
     * @throws HttpClientException
     * @throws SerializerException
     */
    public function get(
        UrlInterface|string $url,
        ?string $as = null,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): object;

    /**
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     *
     * @throws HttpClientException
     */
    public function head(
        UrlInterface|string $url,
        array $query = [],
        array $headers = [],
    ): ClientResponseInterface;

    /**
     * @template T of object
     *
     * @param class-string<T>|null $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return ($as is null ? ClientResponseInterface : T)
     *
     * @throws HttpClientException
     * @throws SerializerException
     */
    public function post(
        UrlInterface|string $url,
        ?string $as = null,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): object;

    /**
     * @template T of object
     *
     * @param class-string<T>|null $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return ($as is null ? ClientResponseInterface : T)
     *
     * @throws HttpClientException
     * @throws SerializerException
     */
    public function put(
        UrlInterface|string $url,
        ?string $as = null,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): object;

    /**
     * @template T of object
     *
     * @param class-string<T>|null $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return ($as is null ? ClientResponseInterface : T)
     *
     * @throws HttpClientException
     * @throws SerializerException
     */
    public function delete(
        UrlInterface|string $url,
        ?string $as = null,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): object;

    /**
     * @template T of object
     *
     * @param class-string<T>|null $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return ($as is null ? ClientResponseInterface : T)
     *
     * @throws HttpClientException
     * @throws SerializerException
     */
    public function connect(
        UrlInterface|string $url,
        ?string $as = null,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): object;

    /**
     * @template T of object
     *
     * @param class-string<T>|null $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return ($as is null ? ClientResponseInterface : T)
     *
     * @throws HttpClientException
     * @throws SerializerException
     */
    public function options(
        UrlInterface|string $url,
        ?string $as = null,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): object;

    /**
     * @template T of object
     *
     * @param class-string<T>|null $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return ($as is null ? ClientResponseInterface : T)
     *
     * @throws HttpClientException
     * @throws SerializerException
     */
    public function trace(
        UrlInterface|string $url,
        ?string $as = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): object;

    /**
     * @template T of object
     *
     * @param class-string<T>|null $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return ($as is null ? ClientResponseInterface : T)
     *
     * @throws HttpClientException
     * @throws SerializerException
     */
    public function patch(
        UrlInterface|string $url,
        ?string $as = null,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): object;

    /**
     * @template T of object
     *
     * @param class-string<T>|null $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return ($as is null ? ClientResponseInterface : T)
     *
     * @throws HttpClientException
     * @throws SerializerException
     */
    public function query(
        UrlInterface|string $url,
        ?string $as = null,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): object;

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return list<T>
     *
     * @throws HttpClientException
     * @throws SerializerException
     */
    public function getList(
        UrlInterface|string $url,
        string $as,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): array;

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return list<T>
     *
     * @throws HttpClientException
     * @throws SerializerException
     */
    public function postList(
        UrlInterface|string $url,
        string $as,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): array;

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return list<T>
     *
     * @throws HttpClientException
     * @throws SerializerException
     */
    public function putList(
        UrlInterface|string $url,
        string $as,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): array;

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return list<T>
     *
     * @throws HttpClientException
     * @throws SerializerException
     */
    public function deleteList(
        UrlInterface|string $url,
        string $as,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): array;

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return list<T>
     *
     * @throws HttpClientException
     * @throws SerializerException
     */
    public function connectList(
        UrlInterface|string $url,
        string $as,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): array;

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return list<T>
     *
     * @throws HttpClientException
     * @throws SerializerException
     */
    public function optionsList(
        UrlInterface|string $url,
        string $as,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): array;

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return list<T>
     *
     * @throws HttpClientException
     * @throws SerializerException
     */
    public function traceList(
        UrlInterface|string $url,
        string $as,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): array;

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return list<T>
     *
     * @throws HttpClientException
     * @throws SerializerException
     */
    public function patchList(
        UrlInterface|string $url,
        string $as,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): array;

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return list<T>
     *
     * @throws HttpClientException
     * @throws SerializerException
     */
    public function queryList(
        UrlInterface|string $url,
        string $as,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): array;

    /**
     * @template T of object
     *
     * @param class-string<T>|null $as
     * @param list<string>|null $only
     * @return ($as is null ? ClientResponseInterface : T)
     *
     * @throws HttpClientException
     * @throws SerializerException
     */
    public function send(
        ClientRequestInterface $request,
        ?string $as = null,
        ?string $key = null,
        ?array $only = null,
    ): object;

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param list<string>|null $only
     * @return list<T>
     *
     * @throws HttpClientException
     * @throws SerializerException
     */
    public function sendList(
        ClientRequestInterface $request,
        string $as,
        ?string $key = null,
        ?array $only = null,
    ): array;

    /**
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param (\Closure(ClientProgressInterface): mixed)|null $progress
     *
     * @throws HttpClientException
     * @throws StreamException
     * @throws UrlException
     */
    public function download(
        UrlInterface|string $url,
        string $path,
        ?int $maxSize = null,
        array $query = [],
        array $headers = [],
        bool $resume = false,
        ?\Closure $progress = null,
    ): ClientResponseInterface;

    /**
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param (\Closure(ClientProgressInterface): mixed)|null $progress
     *
     * @throws FileException
     * @throws HttpClientException
     * @throws StorageException
     * @throws UrlException
     */
    public function downloadTo(
        UrlInterface|string $url,
        StorageInterface $storage,
        string $key,
        ?int $maxSize = null,
        array $query = [],
        array $headers = [],
        ?\Closure $progress = null,
    ): ClientResponseInterface;

    /**
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     *
     * @throws HttpClientException
     * @throws UrlException
     */
    public function stream(
        Method $method,
        UrlInterface|string $url,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
    ): ClientResponseInterface;

    /**
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @return \Generator<int, SseEventInterface>
     *
     * @throws HttpClientException
     * @throws UrlException
     */
    public function streamSse(
        Method $method,
        UrlInterface|string $url,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        int $reconnects = 0,
    ): \Generator;

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @return \Generator<int, T>
     *
     * @throws HttpClientException
     * @throws SerializerException
     * @throws UrlException
     */
    public function streamJson(
        Method $method,
        UrlInterface|string $url,
        string $as,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
    ): \Generator;

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @return \Generator<int, T>
     *
     * @throws HttpClientException
     * @throws SerializerException
     * @throws StreamException
     * @throws UrlException
     */
    public function streamCsv(
        Method $method,
        UrlInterface|string $url,
        string $as,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        string $separator = ',',
        string $enclosure = '"',
    ): \Generator;

    public function deferred(): DeferredHttpClientInterface;

    /**
     * @param PendingResponseInterface<mixed> ...$pending
     */
    public function wait(
        PendingResponseInterface ...$pending,
    ): void;

    /**
     * @template TKey of array-key
     * @template TPending of PendingResponseInterface<mixed>
     *
     * @param iterable<TKey, TPending> $pending
     * @return \Generator<TKey, TPending>
     */
    public function each(
        iterable $pending,
        ?int $concurrency = null,
    ): \Generator;

    /**
     * @param (\Closure(ClientProgressInterface): mixed)|null $progress
     */
    public function withProgress(
        ?\Closure $progress,
    ): static;

    public function withConcurrency(
        int $concurrency,
    ): static;

    /**
     * @throws UrlException
     */
    public function withBaseUrl(
        UrlInterface|string $baseUrl,
    ): static;

    public function withTimeout(
        DurationInterface $timeout,
    ): static;

    public function withConnectTimeout(
        DurationInterface $connectTimeout,
    ): static;

    public function withIdleTimeout(
        ?DurationInterface $idleTimeout,
    ): static;

    public function withMaxResponseSize(
        ?int $maxResponseSize,
    ): static;

    public function withMaxDownloadSize(
        ?int $maxDownloadSize,
    ): static;

    public function withHttpVersion(
        ?HttpVersion $httpVersion,
    ): static;

    public function withProxy(
        ?ProxyInterface $proxy,
    ): static;

    public function withClientCertificate(
        ?ClientCertificateInterface $clientCertificate,
    ): static;

    public function withCertificateAuthority(
        ?string $certificateAuthority,
    ): static;

    public function withHeader(
        HeaderInterface $header,
        bool $replace = false,
    ): static;

    /**
     * @param HeaderInterface[] $headers
     */
    public function withHeaders(
        array $headers,
        bool $replace = false,
    ): static;

    public function withoutHeader(
        string $name,
    ): static;

    public function withUserAgent(
        string $userAgent,
    ): static;

    /**
     * @param array<string, mixed> $query
     */
    public function withQuery(
        array $query,
        bool $replace = false,
    ): static;

    public function withAuth(
        AuthInterface ...$auth,
    ): static;

    public function withMiddleware(
        ClientMiddlewareInterface ...$middleware,
    ): static;

    public function withoutMiddleware(): static;

    public function withRules(
        HttpRulesInterface $rules,
    ): static;

    public function withRedirects(
        RedirectPolicyInterface $redirects,
    ): static;

    public function withoutRedirects(): static;

    public function withRetry(
        ?RetryPolicyInterface $retry,
    ): static;

    public function withCookieJar(
        CookieJarInterface $cookieJar,
    ): static;

    public function withoutCookieJar(): static;

    public function withDecoder(
        ?ResponseDecoderInterface $decoder,
    ): static;

    public function withSkipUnknownKeys(
        bool $skipUnknownKeys,
    ): static;

    public function withSkipValidation(
        bool $skipValidation,
    ): static;

    public function withStrictBaseUrl(
        bool $strictBaseUrl,
    ): static;

    public function withBracketQueryArrays(
        bool $bracketQueryArrays,
    ): static;
}
