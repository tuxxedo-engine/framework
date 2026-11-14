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

use Tuxxedo\Http\Client\Body\RequestBodyInterface;
use Tuxxedo\Http\Client\Request\ClientRequestInterface;
use Tuxxedo\Http\Client\Response\ClientResponseInterface;
use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\Url\UrlException;
use Tuxxedo\Http\Url\UrlInterface;

interface DeferredHttpClientInterface
{
    /**
     * @template T of object
     *
     * @param class-string<T>|null $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return ($as is null ? PendingResponseInterface<ClientResponseInterface> : PendingResponseInterface<T>)
     *
     * @throws UrlException
     */
    #[\NoDiscard]
    public function get(
        UrlInterface|string $url,
        ?string $as = null,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): PendingResponseInterface;

    /**
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @return PendingResponseInterface<ClientResponseInterface>
     *
     * @throws UrlException
     */
    #[\NoDiscard]
    public function head(
        UrlInterface|string $url,
        array $query = [],
        array $headers = [],
    ): PendingResponseInterface;

    /**
     * @template T of object
     *
     * @param class-string<T>|null $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return ($as is null ? PendingResponseInterface<ClientResponseInterface> : PendingResponseInterface<T>)
     *
     * @throws UrlException
     */
    #[\NoDiscard]
    public function post(
        UrlInterface|string $url,
        ?string $as = null,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): PendingResponseInterface;

    /**
     * @template T of object
     *
     * @param class-string<T>|null $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return ($as is null ? PendingResponseInterface<ClientResponseInterface> : PendingResponseInterface<T>)
     *
     * @throws UrlException
     */
    #[\NoDiscard]
    public function put(
        UrlInterface|string $url,
        ?string $as = null,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): PendingResponseInterface;

    /**
     * @template T of object
     *
     * @param class-string<T>|null $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return ($as is null ? PendingResponseInterface<ClientResponseInterface> : PendingResponseInterface<T>)
     *
     * @throws UrlException
     */
    #[\NoDiscard]
    public function delete(
        UrlInterface|string $url,
        ?string $as = null,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): PendingResponseInterface;

    /**
     * @template T of object
     *
     * @param class-string<T>|null $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return ($as is null ? PendingResponseInterface<ClientResponseInterface> : PendingResponseInterface<T>)
     *
     * @throws UrlException
     */
    #[\NoDiscard]
    public function connect(
        UrlInterface|string $url,
        ?string $as = null,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): PendingResponseInterface;

    /**
     * @template T of object
     *
     * @param class-string<T>|null $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return ($as is null ? PendingResponseInterface<ClientResponseInterface> : PendingResponseInterface<T>)
     *
     * @throws UrlException
     */
    #[\NoDiscard]
    public function options(
        UrlInterface|string $url,
        ?string $as = null,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): PendingResponseInterface;

    /**
     * @template T of object
     *
     * @param class-string<T>|null $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return ($as is null ? PendingResponseInterface<ClientResponseInterface> : PendingResponseInterface<T>)
     *
     * @throws UrlException
     */
    #[\NoDiscard]
    public function trace(
        UrlInterface|string $url,
        ?string $as = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): PendingResponseInterface;

    /**
     * @template T of object
     *
     * @param class-string<T>|null $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return ($as is null ? PendingResponseInterface<ClientResponseInterface> : PendingResponseInterface<T>)
     *
     * @throws UrlException
     */
    #[\NoDiscard]
    public function patch(
        UrlInterface|string $url,
        ?string $as = null,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): PendingResponseInterface;

    /**
     * @template T of object
     *
     * @param class-string<T>|null $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return ($as is null ? PendingResponseInterface<ClientResponseInterface> : PendingResponseInterface<T>)
     *
     * @throws UrlException
     */
    #[\NoDiscard]
    public function query(
        UrlInterface|string $url,
        ?string $as = null,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): PendingResponseInterface;

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return PendingResponseInterface<list<T>>
     *
     * @throws UrlException
     */
    #[\NoDiscard]
    public function getList(
        UrlInterface|string $url,
        string $as,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): PendingResponseInterface;

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return PendingResponseInterface<list<T>>
     *
     * @throws UrlException
     */
    #[\NoDiscard]
    public function postList(
        UrlInterface|string $url,
        string $as,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): PendingResponseInterface;

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return PendingResponseInterface<list<T>>
     *
     * @throws UrlException
     */
    #[\NoDiscard]
    public function putList(
        UrlInterface|string $url,
        string $as,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): PendingResponseInterface;

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return PendingResponseInterface<list<T>>
     *
     * @throws UrlException
     */
    #[\NoDiscard]
    public function deleteList(
        UrlInterface|string $url,
        string $as,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): PendingResponseInterface;

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return PendingResponseInterface<list<T>>
     *
     * @throws UrlException
     */
    #[\NoDiscard]
    public function connectList(
        UrlInterface|string $url,
        string $as,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): PendingResponseInterface;

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return PendingResponseInterface<list<T>>
     *
     * @throws UrlException
     */
    #[\NoDiscard]
    public function optionsList(
        UrlInterface|string $url,
        string $as,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): PendingResponseInterface;

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return PendingResponseInterface<list<T>>
     *
     * @throws UrlException
     */
    #[\NoDiscard]
    public function traceList(
        UrlInterface|string $url,
        string $as,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): PendingResponseInterface;

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return PendingResponseInterface<list<T>>
     *
     * @throws UrlException
     */
    #[\NoDiscard]
    public function patchList(
        UrlInterface|string $url,
        string $as,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): PendingResponseInterface;

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param list<string>|null $only
     * @return PendingResponseInterface<list<T>>
     *
     * @throws UrlException
     */
    #[\NoDiscard]
    public function queryList(
        UrlInterface|string $url,
        string $as,
        ?RequestBodyInterface $body = null,
        array $query = [],
        array $headers = [],
        ?string $key = null,
        ?array $only = null,
    ): PendingResponseInterface;

    /**
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param (\Closure(ClientProgressInterface): mixed)|null $progress
     * @return PendingResponseInterface<ClientResponseInterface>
     *
     * @throws UrlException
     */
    #[\NoDiscard]
    public function download(
        UrlInterface|string $url,
        string $path,
        ?int $maxSize = null,
        array $query = [],
        array $headers = [],
        ?\Closure $progress = null,
    ): PendingResponseInterface;

    /**
     * @template T of object
     *
     * @param class-string<T>|null $as
     * @param list<string>|null $only
     * @return ($as is null ? PendingResponseInterface<ClientResponseInterface> : PendingResponseInterface<T>)
     */
    #[\NoDiscard]
    public function send(
        ClientRequestInterface $request,
        ?string $as = null,
        ?string $key = null,
        ?array $only = null,
    ): PendingResponseInterface;

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param list<string>|null $only
     * @return PendingResponseInterface<list<T>>
     */
    #[\NoDiscard]
    public function sendList(
        ClientRequestInterface $request,
        string $as,
        ?string $key = null,
        ?array $only = null,
    ): PendingResponseInterface;
}
