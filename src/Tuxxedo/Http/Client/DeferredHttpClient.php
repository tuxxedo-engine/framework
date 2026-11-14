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
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Url\UrlException;
use Tuxxedo\Http\Url\UrlInterface;

class DeferredHttpClient implements DeferredHttpClientInterface
{
    /**
     * @param \Closure(Method, UrlInterface|string, ?RequestBodyInterface, array<string, mixed>, HeaderInterface[]): ClientRequestInterface $request
     * @param \Closure(ClientRequestInterface, \Closure(ClientResponseInterface): mixed): PendingResponseInterface<mixed> $defer
     * @param \Closure(ClientRequestInterface, ClientResponseInterface, class-string, ?string, list<string>|null): object $decode
     * @param \Closure(ClientRequestInterface, ClientResponseInterface, class-string, ?string, list<string>|null): list<object> $decodeList
     * @param \Closure(UrlInterface|string, string, ?int, array<string, mixed>, HeaderInterface[], (\Closure(ClientProgressInterface): mixed)|null): PendingResponseInterface<ClientResponseInterface> $download
     */
    public function __construct(
        private readonly \Closure $request,
        private readonly \Closure $defer,
        private readonly \Closure $download,
        private readonly \Closure $decode,
        private readonly \Closure $decodeList,
    ) {
    }

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
    ): PendingResponseInterface {
        return $this->single(
            request: ($this->request)(Method::GET, $url, $body, $query, $headers),
            as: $as,
            key: $key,
            only: $only,
        );
    }

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
    ): PendingResponseInterface {
        return $this->single(
            request: ($this->request)(Method::HEAD, $url, null, $query, $headers),
            as: null,
            key: null,
            only: null,
        );
    }

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
    ): PendingResponseInterface {
        return $this->single(
            request: ($this->request)(Method::POST, $url, $body, $query, $headers),
            as: $as,
            key: $key,
            only: $only,
        );
    }

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
    ): PendingResponseInterface {
        return $this->single(
            request: ($this->request)(Method::PUT, $url, $body, $query, $headers),
            as: $as,
            key: $key,
            only: $only,
        );
    }

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
    ): PendingResponseInterface {
        return $this->single(
            request: ($this->request)(Method::DELETE, $url, $body, $query, $headers),
            as: $as,
            key: $key,
            only: $only,
        );
    }

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
    ): PendingResponseInterface {
        return $this->single(
            request: ($this->request)(Method::CONNECT, $url, $body, $query, $headers),
            as: $as,
            key: $key,
            only: $only,
        );
    }

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
    ): PendingResponseInterface {
        return $this->single(
            request: ($this->request)(Method::OPTIONS, $url, $body, $query, $headers),
            as: $as,
            key: $key,
            only: $only,
        );
    }

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
    ): PendingResponseInterface {
        return $this->single(
            request: ($this->request)(Method::TRACE, $url, null, $query, $headers),
            as: $as,
            key: $key,
            only: $only,
        );
    }

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
    ): PendingResponseInterface {
        return $this->single(
            request: ($this->request)(Method::PATCH, $url, $body, $query, $headers),
            as: $as,
            key: $key,
            only: $only,
        );
    }

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
    ): PendingResponseInterface {
        return $this->single(
            request: ($this->request)(Method::QUERY, $url, $body, $query, $headers),
            as: $as,
            key: $key,
            only: $only,
        );
    }

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
    ): PendingResponseInterface {
        return $this->list(
            request: ($this->request)(Method::GET, $url, $body, $query, $headers),
            as: $as,
            key: $key,
            only: $only,
        );
    }

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
    ): PendingResponseInterface {
        return $this->list(
            request: ($this->request)(Method::POST, $url, $body, $query, $headers),
            as: $as,
            key: $key,
            only: $only,
        );
    }

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
    ): PendingResponseInterface {
        return $this->list(
            request: ($this->request)(Method::PUT, $url, $body, $query, $headers),
            as: $as,
            key: $key,
            only: $only,
        );
    }

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
    ): PendingResponseInterface {
        return $this->list(
            request: ($this->request)(Method::DELETE, $url, $body, $query, $headers),
            as: $as,
            key: $key,
            only: $only,
        );
    }

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
    ): PendingResponseInterface {
        return $this->list(
            request: ($this->request)(Method::CONNECT, $url, $body, $query, $headers),
            as: $as,
            key: $key,
            only: $only,
        );
    }

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
    ): PendingResponseInterface {
        return $this->list(
            request: ($this->request)(Method::OPTIONS, $url, $body, $query, $headers),
            as: $as,
            key: $key,
            only: $only,
        );
    }

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
    ): PendingResponseInterface {
        return $this->list(
            request: ($this->request)(Method::TRACE, $url, null, $query, $headers),
            as: $as,
            key: $key,
            only: $only,
        );
    }

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
    ): PendingResponseInterface {
        return $this->list(
            request: ($this->request)(Method::PATCH, $url, $body, $query, $headers),
            as: $as,
            key: $key,
            only: $only,
        );
    }

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
    ): PendingResponseInterface {
        return $this->list(
            request: ($this->request)(Method::QUERY, $url, $body, $query, $headers),
            as: $as,
            key: $key,
            only: $only,
        );
    }

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
    ): PendingResponseInterface {
        return ($this->download)($url, $path, $maxSize, $query, $headers, $progress);
    }

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
    ): PendingResponseInterface {
        return $this->single($request, $as, $key, $only);
    }

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
    ): PendingResponseInterface {
        return $this->list($request, $as, $key, $only);
    }

    /**
     * @template T of object
     *
     * @param class-string<T>|null $as
     * @param list<string>|null $only
     * @return ($as is null ? PendingResponseInterface<ClientResponseInterface> : PendingResponseInterface<T>)
     */
    private function single(
        ClientRequestInterface $request,
        ?string $as,
        ?string $key,
        ?array $only,
    ): PendingResponseInterface {
        if ($as === null) {
            /** @var PendingResponseInterface<ClientResponseInterface> $pending */
            $pending = ($this->defer)(
                $request,
                static fn (ClientResponseInterface $response): ClientResponseInterface => $response,
            );

            return $pending;
        }

        /** @var PendingResponseInterface<T> $pending */
        $pending = ($this->defer)(
            $request,
            fn (ClientResponseInterface $response): object => ($this->decode)($request, $response, $as, $key, $only),
        );

        return $pending;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param list<string>|null $only
     * @return PendingResponseInterface<list<T>>
     */
    private function list(
        ClientRequestInterface $request,
        string $as,
        ?string $key,
        ?array $only,
    ): PendingResponseInterface {
        /** @var PendingResponseInterface<list<T>> $pending */
        $pending = ($this->defer)(
            $request,
            fn (ClientResponseInterface $response): array => ($this->decodeList)($request, $response, $as, $key, $only),
        );

        return $pending;
    }
}
