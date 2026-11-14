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

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DefaultInitializer;
use Tuxxedo\File\FileException;
use Tuxxedo\File\LocalFile;
use Tuxxedo\File\Storage\StorageException;
use Tuxxedo\File\Storage\StorageInterface;
use Tuxxedo\Http\Client\Auth\AuthInterface;
use Tuxxedo\Http\Client\Auth\RefreshableAuthInterface;
use Tuxxedo\Http\Client\Body\RawBody;
use Tuxxedo\Http\Client\Body\RequestBodyInterface;
use Tuxxedo\Http\Client\Decoder\JsonDecoder;
use Tuxxedo\Http\Client\Decoder\ResponseDecoderInterface;
use Tuxxedo\Http\Client\Decoder\XmlDecoder;
use Tuxxedo\Http\Client\Request\ClientRequest;
use Tuxxedo\Http\Client\Request\ClientRequestInterface;
use Tuxxedo\Http\Client\Response\ClientResponse;
use Tuxxedo\Http\Client\Response\ClientResponseInfo;
use Tuxxedo\Http\Client\Response\ClientResponseInfoInterface;
use Tuxxedo\Http\Client\Response\ClientResponseInterface;
use Tuxxedo\Http\Client\Stream\LineReader;
use Tuxxedo\Http\Client\Stream\SseReader;
use Tuxxedo\Http\Client\Stream\StreamResource;
use Tuxxedo\Http\Client\Transport\CurlTransport;
use Tuxxedo\Http\Client\Transport\TransportInterface;
use Tuxxedo\Http\Client\Transport\TransportOptions;
use Tuxxedo\Http\Client\Transport\TransportOptionsInterface;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\HttpVersion;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Stream\ResourceStream;
use Tuxxedo\Http\Stream\ResourceStreamInterface;
use Tuxxedo\Http\Stream\SseEventInterface;
use Tuxxedo\Http\Stream\StreamException;
use Tuxxedo\Http\Stream\StreamInterface;
use Tuxxedo\Http\Url\Url;
use Tuxxedo\Http\Url\UrlException;
use Tuxxedo\Http\Url\UrlInterface;
use Tuxxedo\Serializer\JsonSerializerInterface;
use Tuxxedo\Serializer\SerializerException;
use Tuxxedo\Serializer\SerializerInputException;
use Tuxxedo\Serializer\SerializerManagerInterface;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\DurationInterface;
use Tuxxedo\Validator\ValidationException;
use Tuxxedo\Version;

#[DefaultInitializer(
    static function (ContainerInterface $container): HttpClientInterface {
        return new HttpClient(
            serializerManager: $container->resolve(SerializerManagerInterface::class),
        );
    },
)]
class HttpClient implements HttpClientInterface
{
    private const array IDEMPOTENT_METHODS = [
        'GET',
        'HEAD',
        'PUT',
        'DELETE',
        'OPTIONS',
        'TRACE',
        'QUERY',
    ];

    private const array REDIRECT_STATUSES = [
        301,
        302,
        303,
        307,
        308,
    ];

    private const array POST_TO_GET_STATUSES = [
        301,
        302,
    ];

    private const array CREDENTIAL_HEADERS = [
        'authorization',
        'cookie',
        'proxy-authorization',
    ];

    private readonly ?UrlInterface $baseUrl;
    private readonly DurationInterface $connectTimeout;
    private readonly DurationInterface $timeout;
    private readonly ?DurationInterface $idleTimeout;
    private readonly string $userAgent;
    private readonly TransportInterface $transport;

    /**
     * @var list<AuthInterface>
     */
    private readonly array $auth;

    /**
     * @var list<HttpRulesInterface>
     */
    private readonly array $rules;

    /**
     * @var list<ClientMiddlewareInterface>
     */
    private readonly array $middleware;

    /**
     * @param HeaderInterface[] $headers
     * @param array<string, mixed> $query
     * @param AuthInterface[] $auth
     * @param ClientMiddlewareInterface[] $middleware
     *
     * @throws HttpClientException
     * @throws UrlException
     */
    public function __construct(
        private readonly SerializerManagerInterface $serializerManager,
        UrlInterface|string|null $baseUrl = null,
        private readonly array $headers = [],
        private readonly array $query = [],
        array $auth = [],
        ?HttpRulesInterface $rules = null,
        array $middleware = [],
        ?DurationInterface $connectTimeout = null,
        ?DurationInterface $timeout = null,
        ?DurationInterface $idleTimeout = null,
        private readonly ?int $maxResponseSize = 16 * 1024 * 1024,
        private readonly ?int $maxDownloadSize = null,
        private readonly ?HttpVersion $httpVersion = null,
        private readonly ?ProxyInterface $proxy = null,
        private readonly ?ClientCertificateInterface $clientCertificate = null,
        private readonly ?string $certificateAuthority = null,
        private readonly RedirectPolicyInterface $redirects = new RedirectPolicy(),
        private readonly ?RetryPolicyInterface $retry = null,
        private readonly ?CookieJarInterface $cookieJar = null,
        private readonly ?ResponseDecoderInterface $decoder = null,
        private readonly bool $skipUnknownKeys = true,
        private readonly bool $skipValidation = false,
        private readonly bool $bracketQueryArrays = true,
        private readonly bool $strictBaseUrl = false,
        private readonly int $concurrency = 10,
        private readonly ?\Closure $progress = null,
        ?string $userAgent = null,
        bool $exposeEngineVersion = false,
        ?TransportInterface $transport = null,
    ) {
        $this->baseUrl = \is_string($baseUrl)
            ? Url::parse($baseUrl)
            : $baseUrl;

        $this->auth = \array_values($auth);
        $this->middleware = \array_values($middleware);
        $this->rules = $rules !== null
            ? [
                $rules,
            ]
            : [];

        $this->connectTimeout = $connectTimeout ?? Duration::fromSeconds(10);
        $this->timeout = $timeout ?? Duration::fromSeconds(30);
        $this->idleTimeout = $idleTimeout ?? Duration::fromSeconds(30);
        $this->userAgent = $userAgent ?? match ($exposeEngineVersion) {
            true => 'Tuxxedo Engine/' . Version::SIMPLE,
            false => 'Tuxxedo Engine',
        };

        $this->transport = $transport ?? new CurlTransport($serializerManager);
    }

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
    ): object {
        return $this->call(
            method: Method::GET,
            url: $url,
            as: $as,
            body: $body,
            query: $query,
            headers: $headers,
            key: $key,
            only: $only,
        );
    }

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
    ): ClientResponseInterface {
        return $this->execute(
            $this->request(
                method: Method::HEAD,
                url: $url,
                body: null,
                query: $query,
                headers: $headers,
            ),
        );
    }

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
    ): object {
        return $this->call(
            method: Method::POST,
            url: $url,
            as: $as,
            body: $body,
            query: $query,
            headers: $headers,
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
    ): object {
        return $this->call(
            method: Method::PUT,
            url: $url,
            as: $as,
            body: $body,
            query: $query,
            headers: $headers,
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
    ): object {
        return $this->call(
            method: Method::DELETE,
            url: $url,
            as: $as,
            body: $body,
            query: $query,
            headers: $headers,
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
    ): object {
        return $this->call(
            method: Method::CONNECT,
            url: $url,
            as: $as,
            body: $body,
            query: $query,
            headers: $headers,
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
    ): object {
        return $this->call(
            method: Method::OPTIONS,
            url: $url,
            as: $as,
            body: $body,
            query: $query,
            headers: $headers,
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
    ): object {
        return $this->call(
            method: Method::TRACE,
            url: $url,
            as: $as,
            body: null,
            query: $query,
            headers: $headers,
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
    ): object {
        return $this->call(
            method: Method::PATCH,
            url: $url,
            as: $as,
            body: $body,
            query: $query,
            headers: $headers,
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
    ): object {
        return $this->call(
            method: Method::QUERY,
            url: $url,
            as: $as,
            body: $body,
            query: $query,
            headers: $headers,
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
    ): array {
        return $this->callList(
            method: Method::GET,
            url: $url,
            as: $as,
            body: $body,
            query: $query,
            headers: $headers,
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
    ): array {
        return $this->callList(
            method: Method::POST,
            url: $url,
            as: $as,
            body: $body,
            query: $query,
            headers: $headers,
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
    ): array {
        return $this->callList(
            method: Method::PUT,
            url: $url,
            as: $as,
            body: $body,
            query: $query,
            headers: $headers,
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
    ): array {
        return $this->callList(
            method: Method::DELETE,
            url: $url,
            as: $as,
            body: $body,
            query: $query,
            headers: $headers,
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
    ): array {
        return $this->callList(
            method: Method::CONNECT,
            url: $url,
            as: $as,
            body: $body,
            query: $query,
            headers: $headers,
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
    ): array {
        return $this->callList(
            method: Method::OPTIONS,
            url: $url,
            as: $as,
            body: $body,
            query: $query,
            headers: $headers,
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
    ): array {
        return $this->callList(
            method: Method::TRACE,
            url: $url,
            as: $as,
            body: null,
            query: $query,
            headers: $headers,
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
    ): array {
        return $this->callList(
            method: Method::PATCH,
            url: $url,
            as: $as,
            body: $body,
            query: $query,
            headers: $headers,
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
    ): array {
        return $this->callList(
            method: Method::QUERY,
            url: $url,
            as: $as,
            body: $body,
            query: $query,
            headers: $headers,
            key: $key,
            only: $only,
        );
    }

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
    ): object {
        $response = $this->execute($request);

        if ($as === null) {
            return $response;
        }

        return $this->decode(
            request: $request,
            response: $response,
            as: $as,
            key: $key,
            only: $only,
        );
    }

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
    ): array {
        return $this->decodeList(
            request: $request,
            response: $this->execute($request),
            as: $as,
            key: $key,
            only: $only,
        );
    }


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
    ): ClientResponseInterface {
        $client = $progress !== null
            ? $this->withProgress($progress)
            : $this;

        $response = $client->fetch(
            request: $this->request(Method::GET, $url, null, $query, $headers),
            path: $path,
            maxSize: $maxSize ?? $this->maxDownloadSize,
            resume: $resume,
        );

        return new ClientResponse(
            content: ResourceStream::fromFile($path),
            headers: $response->headers->all(),
            statusCode: $response->statusCode,
            httpVersion: $response->httpVersion,
            info: $response->info,
            serializerManager: $this->serializerManager,
        );
    }

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
    ): ClientResponseInterface {
        $client = $progress !== null
            ? $this->withProgress($progress)
            : $this;

        $path = \sys_get_temp_dir() . '/tuxxedo-download-' . \bin2hex(\random_bytes(8));
        $response = $client->fetch(
            request: $this->request(Method::GET, $url, null, $query, $headers),
            path: $path,
            maxSize: $maxSize ?? $this->maxDownloadSize,
        );

        try {
            $storage->write(
                key: $key,
                file: new LocalFile(
                    path: $path,
                    name: \basename($key),
                    mimeType: $response->headers->has('Content-Type')
                        ? $response->headers->string('Content-Type')
                        : null,
                ),
            );
        } finally {
            @\unlink($path);
        }

        return new ClientResponse(
            headers: $response->headers->all(),
            statusCode: $response->statusCode,
            httpVersion: $response->httpVersion,
            info: $response->info,
            serializerManager: $this->serializerManager,
        );
    }

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
    ): ClientResponseInterface {
        return $this->open(
            $this->request($method, $url, $body, $query, $headers),
        );
    }

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
    ): \Generator {
        $request = $this->request($method, $url, $body, $query, $headers);

        return $this->events(
            request: $request,
            response: $this->openSse($request),
            reconnects: $reconnects,
        );
    }

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
    ): \Generator {
        $request = $this->request($method, $url, $body, $query, $headers);
        $response = $this->open($request);

        return $this->records(
            request: $request,
            response: $response,
            records: self::jsonRecords(
                serializer: $this->serializerManager->json(
                    skipUnknownKeys: $this->skipUnknownKeys,
                    skipValidation: $this->skipValidation,
                ),
                stream: $response->body->getStream(),
                as: $as,
            ),
        );
    }

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
    ): \Generator {
        $request = $this->request($method, $url, $body, $query, $headers);
        $response = $this->open($request);

        return $this->records(
            request: $request,
            response: $response,
            records: $this->serializerManager->csv(
                skipUnknownKeys: $this->skipUnknownKeys,
                skipValidation: $this->skipValidation,
                separator: $separator,
                enclosure: $enclosure,
            )->decodeStream(
                StreamResource::open($response->body->getStream()),
                $as,
            ),
        );
    }

    public function deferred(): DeferredHttpClientInterface
    {
        return new DeferredHttpClient(
            request: $this->request(...),
            defer: $this->defer(...),
            download: $this->deferDownload(...),
            decode: $this->decode(...),
            decodeList: $this->decodeList(...),
        );
    }

    /**
     * @param PendingResponseInterface<mixed> ...$pending
     */
    public function wait(
        PendingResponseInterface ...$pending,
    ): void {
        \iterator_to_array(ClientScheduler::run($pending, $this->concurrency), false);
    }

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
    ): \Generator {
        return ClientScheduler::run($pending, $concurrency ?? $this->concurrency);
    }

    /**
     * @param (\Closure(ClientProgressInterface): mixed)|null $progress
     */
    public function withProgress(
        ?\Closure $progress,
    ): static {
        return clone (
            $this,
            [
                'progress' => $progress,
            ],
        );
    }

    public function withConcurrency(
        int $concurrency,
    ): static {
        return clone (
            $this,
            [
                'concurrency' => $concurrency,
            ],
        );
    }

    /**
     * @throws UrlException
     */
    public function withBaseUrl(
        UrlInterface|string $baseUrl,
    ): static {
        return clone (
            $this,
            [
                'baseUrl' => $baseUrl instanceof UrlInterface
                    ? $baseUrl
                    : Url::parse($baseUrl),
            ],
        );
    }

    public function withTimeout(
        DurationInterface $timeout,
    ): static {
        return clone (
            $this,
            [
                'timeout' => $timeout,
            ],
        );
    }

    public function withConnectTimeout(
        DurationInterface $connectTimeout,
    ): static {
        return clone (
            $this,
            [
                'connectTimeout' => $connectTimeout,
            ],
        );
    }

    public function withIdleTimeout(
        ?DurationInterface $idleTimeout,
    ): static {
        return clone (
            $this,
            [
                'idleTimeout' => $idleTimeout,
            ],
        );
    }

    public function withMaxResponseSize(
        ?int $maxResponseSize,
    ): static {
        return clone (
            $this,
            [
                'maxResponseSize' => $maxResponseSize,
            ],
        );
    }

    public function withMaxDownloadSize(
        ?int $maxDownloadSize,
    ): static {
        return clone (
            $this,
            [
                'maxDownloadSize' => $maxDownloadSize,
            ],
        );
    }

    public function withHttpVersion(
        ?HttpVersion $httpVersion,
    ): static {
        return clone (
            $this,
            [
                'httpVersion' => $httpVersion,
            ],
        );
    }

    public function withProxy(
        ?ProxyInterface $proxy,
    ): static {
        return clone (
            $this,
            [
                'proxy' => $proxy,
            ],
        );
    }

    public function withClientCertificate(
        ?ClientCertificateInterface $clientCertificate,
    ): static {
        return clone (
            $this,
            [
                'clientCertificate' => $clientCertificate,
            ],
        );
    }

    public function withCertificateAuthority(
        ?string $certificateAuthority,
    ): static {
        return clone (
            $this,
            [
                'certificateAuthority' => $certificateAuthority,
            ],
        );
    }

    public function withHeader(
        HeaderInterface $header,
        bool $replace = false,
    ): static {
        return $this->withHeaders(
            headers: [
                $header,
            ],
            replace: $replace,
        );
    }

    /**
     * @param HeaderInterface[] $headers
     */
    public function withHeaders(
        array $headers,
        bool $replace = false,
    ): static {
        $current = $this->headers;

        if ($replace) {
            foreach ($headers as $header) {
                $current = self::withoutNamed($current, $header->name);
            }
        }

        return clone (
            $this,
            [
                'headers' => \array_merge(
                    $current,
                    \array_values($headers),
                ),
            ],
        );
    }

    public function withoutHeader(
        string $name,
    ): static {
        return clone (
            $this,
            [
                'headers' => self::withoutNamed($this->headers, $name),
            ],
        );
    }

    public function withUserAgent(
        string $userAgent,
    ): static {
        return clone (
            $this,
            [
                'userAgent' => $userAgent,
            ],
        );
    }

    /**
     * @param array<string, mixed> $query
     */
    public function withQuery(
        array $query,
        bool $replace = false,
    ): static {
        return clone (
            $this,
            [
                'query' => $replace
                    ? $query
                    : \array_replace($this->query, $query),
            ],
        );
    }

    public function withAuth(
        AuthInterface ...$auth,
    ): static {
        return clone (
            $this,
            [
                'auth' => \array_values($auth),
            ],
        );
    }

    public function withMiddleware(
        ClientMiddlewareInterface ...$middleware,
    ): static {
        return clone (
            $this,
            [
                'middleware' => [
                    ...$this->middleware,
                    ...\array_values($middleware),
                ],
            ],
        );
    }

    public function withoutMiddleware(): static
    {
        return clone (
            $this,
            [
                'middleware' => [],
            ],
        );
    }

    public function withRules(
        HttpRulesInterface $rules,
    ): static {
        return clone (
            $this,
            [
                'rules' => [
                    ...$this->rules,
                    $rules,
                ],
            ],
        );
    }

    public function withRedirects(
        RedirectPolicyInterface $redirects,
    ): static {
        return clone (
            $this,
            [
                'redirects' => $redirects,
            ],
        );
    }

    public function withoutRedirects(): static
    {
        return clone (
            $this,
            [
                'redirects' => new RedirectPolicy(
                    max: 0,
                ),
            ],
        );
    }

    public function withRetry(
        ?RetryPolicyInterface $retry,
    ): static {
        return clone (
            $this,
            [
                'retry' => $retry,
            ],
        );
    }

    public function withCookieJar(
        CookieJarInterface $cookieJar,
    ): static {
        return clone (
            $this,
            [
                'cookieJar' => $cookieJar,
            ],
        );
    }

    public function withoutCookieJar(): static
    {
        return clone (
            $this,
            [
                'cookieJar' => null,
            ],
        );
    }

    public function withDecoder(
        ?ResponseDecoderInterface $decoder,
    ): static {
        return clone (
            $this,
            [
                'decoder' => $decoder,
            ],
        );
    }

    public function withSkipUnknownKeys(
        bool $skipUnknownKeys,
    ): static {
        return clone (
            $this,
            [
                'skipUnknownKeys' => $skipUnknownKeys,
            ],
        );
    }

    public function withSkipValidation(
        bool $skipValidation,
    ): static {
        return clone (
            $this,
            [
                'skipValidation' => $skipValidation,
            ],
        );
    }

    public function withStrictBaseUrl(
        bool $strictBaseUrl,
    ): static {
        return clone (
            $this,
            [
                'strictBaseUrl' => $strictBaseUrl,
            ],
        );
    }

    public function withBracketQueryArrays(
        bool $bracketQueryArrays,
    ): static {
        return clone (
            $this,
            [
                'bracketQueryArrays' => $bracketQueryArrays,
            ],
        );
    }

    /**
     * @template T of object
     *
     * @param class-string<T>|null $as
     * @param HeaderInterface[] $headers
     * @param array<string, mixed> $query
     * @param list<string>|null $only
     * @return ($as is null ? ClientResponseInterface : T)
     *
     * @throws HttpClientException
     * @throws SerializerException
     * @throws UrlException
     */
    private function call(
        Method $method,
        UrlInterface|string $url,
        ?string $as,
        ?RequestBodyInterface $body,
        array $query,
        array $headers,
        ?string $key,
        ?array $only,
    ): object {
        return $this->send(
            request: $this->request(
                method: $method,
                url: $url,
                body: $body,
                query: $query,
                headers: $headers,
            ),
            as: $as,
            key: $key,
            only: $only,
        );
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param HeaderInterface[] $headers
     * @param array<string, mixed> $query
     * @param list<string>|null $only
     * @return list<T>
     *
     * @throws HttpClientException
     * @throws SerializerException
     * @throws UrlException
     */
    private function callList(
        Method $method,
        UrlInterface|string $url,
        string $as,
        ?RequestBodyInterface $body,
        array $query,
        array $headers,
        ?string $key,
        ?array $only,
    ): array {
        return $this->sendList(
            request: $this->request(
                method: $method,
                url: $url,
                body: $body,
                query: $query,
                headers: $headers,
            ),
            as: $as,
            key: $key,
            only: $only,
        );
    }

    /**
     * @param HeaderInterface[] $headers
     * @param array<string, mixed> $query
     *
     * @throws UrlException
     */
    private function request(
        Method $method,
        UrlInterface|string $url,
        ?RequestBodyInterface $body,
        array $query,
        array $headers,
    ): ClientRequestInterface {
        return new ClientRequest(
            method: $method,
            url: $url,
            headers: $headers,
            body: $body,
            query: $query,
        );
    }


    /**
     * @throws HttpClientException
     */
    private function openSse(
        ClientRequestInterface $request,
    ): ?ClientResponseInterface {
        $response = $this->open($request);

        if ($response->statusCode === 204) {
            return null;
        }

        if (
            !$response->headers->has('Content-Type') ||
            \strtolower(\trim(\explode(';', $response->headers->string('Content-Type'))[0])) !== 'text/event-stream'
        ) {
            throw HttpClientException::fromUnsupportedContentType($request, $response);
        }

        return $response;
    }

    /**
     * @return \Generator<int, SseEventInterface>
     *
     * @throws HttpClientException
     * @throws StreamException
     */
    private function events(
        ClientRequestInterface $request,
        ?ClientResponseInterface $response,
        int $reconnects,
    ): \Generator {
        $lastEventId = null;
        $retry = 3000;

        while ($response !== null) {
            try {
                foreach (SseReader::read($response->body->getStream()) as $event) {
                    $lastEventId = $event->id ?? $lastEventId;
                    $retry = $event->retry ?? $retry;

                    yield $event;
                }
            } catch (HttpClientException $exception) {
                if ($reconnects === 0) {
                    throw $exception;
                }
            }

            if ($reconnects-- === 0) {
                return;
            }

            \usleep($retry * 1000);

            $response = $this->openSse(
                $lastEventId !== null
                    ? $request->withHeader(
                        header: new Header('Last-Event-ID', $lastEventId),
                        replace: true,
                    )
                    : $request,
            );
        }
    }

    /**
     * @throws HttpClientException
     */
    private function fetch(
        ClientRequestInterface $request,
        string $path,
        ?int $maxSize,
        bool $resume = false,
    ): ClientResponseInterface {
        $temporary = $resume
            ? $path . '.part'
            : $path . '.' . \bin2hex(\random_bytes(6)) . '.part';

        $offset = $resume && \is_file($temporary)
            ? (int) \filesize($temporary)
            : 0;

        if ($offset > 0) {
            $request = $request->withHeader(
                header: new Header('Range', 'bytes=' . $offset . '-'),
                replace: true,
            );
        }

        $response = $this->execute($request, streaming: true);

        if ($offset > 0 && $response->statusCode === 416) {
            return $this->complete($request, $temporary, $path, $response);
        }

        if ($response->statusCode < 200 || $response->statusCode > 299) {
            throw HttpClientException::fromUnexpectedStatus($request, $response);
        }

        $append = $offset > 0 && $response->statusCode === 206;
        $stream = $response->body->getStream();
        $file = @\fopen(
            $temporary,
            $append
                ? 'ab'
                : 'wb',
        );

        if ($file === false) {
            throw HttpClientException::fromUnwritableDownload($request, $path);
        }

        $size = $append
            ? $offset
            : 0;

        $complete = false;

        try {
            while (($chunk = $stream->read()) !== null) {
                $size += \strlen($chunk);

                if ($maxSize !== null && $size > $maxSize) {
                    throw HttpClientException::fromResponseTooLarge($request, $maxSize);
                }

                if (\fwrite($file, $chunk) !== \strlen($chunk)) {
                    throw HttpClientException::fromUnwritableDownload($request, $path); // @codeCoverageIgnore
                }
            }

            $complete = true;
        } finally {
            \fclose($file);

            if (!$complete && !$resume) {
                @\unlink($temporary);
            }
        }

        return $this->complete($request, $temporary, $path, $response);
    }

    /**
     * @throws HttpClientException
     */
    private function complete(
        ClientRequestInterface $request,
        string $temporary,
        string $path,
        ClientResponseInterface $response,
    ): ClientResponseInterface {
        if (!@\rename($temporary, $path)) {
            // @codeCoverageIgnoreStart
            @\unlink($temporary);

            throw HttpClientException::fromUnwritableDownload($request, $path);
            // @codeCoverageIgnoreEnd
        }

        return $response;
    }

    /**
     * @throws HttpClientException
     */
    private function open(
        ClientRequestInterface $request,
    ): ClientResponseInterface {
        $response = $this->execute($request, streaming: true);

        if ($response->statusCode < 200 || $response->statusCode > 299) {
            throw HttpClientException::fromUnexpectedStatus($request, $response);
        }

        return $response;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @return \Generator<int, T>
     *
     * @throws SerializerException
     * @throws ValidationException
     */
    private static function jsonRecords(
        JsonSerializerInterface $serializer,
        StreamInterface $stream,
        string $as,
    ): \Generator {
        foreach (LineReader::read($stream) as $line) {
            $line = \trim($line, "\x1E \t");

            if ($line !== '') {
                yield $serializer->decode($line, $as);
            }
        }
    }

    /**
     * @template T of object
     *
     * @param \Generator<array-key, T> $records
     * @return \Generator<int, T>
     *
     * @throws HttpClientException
     */
    private function records(
        ClientRequestInterface $request,
        ClientResponseInterface $response,
        \Generator $records,
    ): \Generator {
        try {
            foreach ($records as $record) {
                yield $record;
            }
        } catch (ValidationException $exception) {
            throw HttpClientException::fromInvalidBody($request, $response, $exception);
        } catch (SerializerInputException|\JsonException $exception) {
            throw HttpClientException::fromMalformedBody($request, $response, $exception);
        }
    }

    /**
     * @param \Closure(ClientResponseInterface): mixed $decode
     * @return PendingResponse<mixed>
     */
    private function defer(
        ClientRequestInterface $request,
        \Closure $decode,
    ): PendingResponse {
        return new PendingResponse(
            transport: $this->transport,
            options: $this->transportOptions(),
            pipeline: $this->pipeline($request),
            decode: $decode,
        );
    }

    /**
     * @param array<string, mixed> $query
     * @param HeaderInterface[] $headers
     * @param (\Closure(ClientProgressInterface): mixed)|null $progress
     * @return PendingResponse<ClientResponseInterface>
     *
     * @throws UrlException
     */
    private function deferDownload(
        UrlInterface|string $url,
        string $path,
        ?int $maxSize,
        array $query,
        array $headers,
        ?\Closure $progress,
    ): PendingResponse {
        $request = $this->request(Method::GET, $url, null, $query, $headers);
        $temporary = $path . '.' . \bin2hex(\random_bytes(6)) . '.part';

        return new PendingResponse(
            transport: $this->transport,
            options: $this->transportOptions(
                sink: $temporary,
                maxSize: $maxSize ?? $this->maxDownloadSize,
                progress: $progress,
            ),
            pipeline: $this->pipeline($request),
            decode: fn (ClientResponseInterface $response): ClientResponseInterface => $this->downloaded($request, $response, $temporary, $path),
            failed: static function () use ($temporary): void {
                @\unlink($temporary);
            },
        );
    }

    /**
     * @throws HttpClientException
     * @throws StreamException
     */
    private function downloaded(
        ClientRequestInterface $request,
        ClientResponseInterface $response,
        string $temporary,
        string $path,
    ): ClientResponseInterface {
        if ($response->statusCode < 200 || $response->statusCode > 299) {
            throw HttpClientException::fromUnexpectedStatus($request, $response);
        }

        if (!\is_file($temporary) && @\file_put_contents($temporary, $response->body->getRaw()) === false) {
            throw HttpClientException::fromUnwritableDownload($request, $path);
        }

        $this->complete($request, $temporary, $path, $response);

        return new ClientResponse(
            content: ResourceStream::fromFile($path),
            headers: $response->headers->all(),
            statusCode: $response->statusCode,
            httpVersion: $response->httpVersion,
            info: $response->info,
            serializerManager: $this->serializerManager,
        );
    }

    /**
     * @throws HttpClientException
     */
    private function execute(
        ClientRequestInterface $request,
        bool $streaming = false,
    ): ClientResponseInterface {
        $options = $this->transportOptions($streaming);
        $pipeline = $this->pipeline($request);

        while ($pipeline->valid()) {
            $step = $pipeline->current();

            if (\is_int($step)) {
                \usleep($step);

                $pipeline->send(null);

                continue;
            }

            $pipeline->send($this->transmit($step, $options, $streaming));
        }

        return $pipeline->getReturn();
    }

    /**
     * @return \Generator<int, ClientRequestInterface|int, ClientResponseInterface|HttpClientException|null, ClientResponseInterface>
     *
     * @throws HttpClientException
     */
    private function pipeline(
        ClientRequestInterface $request,
    ): \Generator {
        $current = $this->resolve($request);
        $origin = self::origin($current->url);
        $previous = [];
        $attempt = 0;
        $refreshed = false;

        while (true) {
            $authenticated = $this->authenticates($current->url);
            $sending = $authenticated
                ? $this->authenticate($current)
                : $current;

            $result = yield from $this->through($sending, 0);

            if ($result instanceof HttpClientException) {
                $retry = $this->retryPolicy($current, $attempt, null);

                if ($retry === null) {
                    throw $result;
                }

                yield self::delay($retry, $attempt++, null);

                continue;
            }

            $this->cookieJar?->store($result->cookies, $result->info->effectiveUrl);

            if (
                $authenticated &&
                !$refreshed &&
                $result->statusCode === 401 &&
                self::replayable($current) &&
                $this->refresh($result)
            ) {
                $refreshed = true;

                continue;
            }

            $retry = $this->retryPolicy($current, $attempt, $result);

            if ($retry !== null) {
                yield self::delay($retry, $attempt++, $result);

                continue;
            }

            $next = $this->redirect($current, $result, $origin, \sizeof($previous));

            if ($next === null) {
                break;
            }

            $previous[] = $result->info;
            $current = $next;
            $attempt = 0;
        }

        if (\sizeof($previous) === 0) {
            return $result;
        }

        return $this->adopt($result, ClientResponseInfo::chain($result->info, $previous));
    }

    /**
     * @return \Generator<int, ClientRequestInterface|int, ClientResponseInterface|HttpClientException|null, ClientResponseInterface|HttpClientException>
     *
     * @throws HttpClientException
     */
    private function through(
        ClientRequestInterface $request,
        int $index,
    ): \Generator {
        if (!isset($this->middleware[$index])) {
            $result = $this->intercept($request) ?? (yield $request);

            return $result ?? HttpClientException::fromMissingTransportResult($request);
        }

        $middleware = $this->middleware[$index]->handle($request);
        $result = null;

        while ($middleware->valid()) {
            $step = $middleware->current();

            if ($step instanceof DurationInterface) {
                yield self::microseconds($step);

                $middleware->send(null);

                continue;
            }

            $result = yield from $this->through($step, $index + 1);

            $middleware->send($result);
        }

        $returned = $middleware->getReturn();

        if ($returned instanceof ClientResponseInterface && $returned !== $result) {
            return $this->adopt($returned);
        }

        return $returned ?? $result ?? HttpClientException::fromMissingTransportResult($request);
    }

    /**
     * @throws HttpClientException
     */
    private function intercept(
        ClientRequestInterface $request,
    ): ClientResponseInterface|HttpClientException|null {
        if ($request->body instanceof RawBody && $request->body->body instanceof ResourceStreamInterface) {
            $request->body->body->rewind();
        }

        $faked = null;

        foreach ($this->rules as $rules) {
            $result = $rules->evaluate($request);
            $faked ??= $result;
        }

        if (!$faked instanceof ClientResponseInterface) {
            return $faked;
        }

        return $this->adopt(
            $faked,
            new ClientResponseInfo(
                [
                    'url' => (string) $request->url,
                    'http_code' => $faked->statusCode,
                ],
            ),
        );
    }

    private function adopt(
        ClientResponseInterface $response,
        ?ClientResponseInfoInterface $info = null,
    ): ClientResponseInterface {
        return new ClientResponse(
            content: $response->buffered
                ? $response->body->getRaw()
                : $response->body->getStream(),
            headers: $response->headers->all(),
            statusCode: $response->statusCode,
            httpVersion: $response->httpVersion,
            info: $info ?? $response->info,
            serializerManager: $this->serializerManager,
        );
    }

    /**
     * @throws HttpClientException
     */
    private function transmit(
        ClientRequestInterface $request,
        TransportOptionsInterface $options,
        bool $streaming,
    ): ClientResponseInterface|HttpClientException {
        if ($streaming) {
            return $this->transport->stream($request, $options);
        }

        foreach (
            $this->transport->send(
                [
                    $request,
                ],
                $options,
            ) as $result
        ) {
            return $result;
        }

        throw HttpClientException::fromMissingTransportResult($request);
    }

    /**
     * @throws HttpClientException
     */
    private function resolve(
        ClientRequestInterface $request,
    ): ClientRequestInterface {
        $url = $request->url;

        if (\preg_match('#(^|/)\.{1,2}(/|$)#', $url->path) === 1) {
            throw HttpClientException::fromDotSegments($request);
        }

        if ($this->baseUrl !== null && $url->scheme === '') {
            $url = $url->host !== ''
                ? $url->withScheme($this->baseUrl->scheme)
                : self::join($this->baseUrl, $url);
        }

        if ($this->strictBaseUrl && !$this->sameOrigin($url)) {
            throw HttpClientException::fromForeignOrigin($request->withUrl($url));
        }

        $query = $this->encodeQuery(
            \array_replace($this->query, $request->query),
        );

        if ($query !== '') {
            $url = $url->withQuery(
                $url->query !== ''
                    ? $url->query . '&' . $query
                    : $query,
            );
        }

        $headers = [];

        $sameOrigin = $this->sameOrigin($url);

        foreach ($this->headers as $header) {
            if (
                !$request->hasHeader($header->name) &&
                (
                    $sameOrigin ||
                    !\in_array(\strtolower($header->name), self::CREDENTIAL_HEADERS, true)
                )
            ) {
                $headers[] = $header;
            }
        }

        $resolved = $request
            ->withUrl($url)
            ->withHeaders($headers)
            ->withQuery(
                [],
                replace: true,
            )
            ->withHttpVersion($request->httpVersion ?? $this->httpVersion);

        if (!$resolved->hasHeader('User-Agent')) {
            $resolved = $resolved->withHeader(new Header('User-Agent', $this->userAgent));
        }

        return $this->withCookies($resolved);
    }

    private function authenticates(
        UrlInterface $url,
    ): bool {
        return \sizeof($this->auth) > 0 && $this->sameOrigin($url);
    }

    private function sameOrigin(
        UrlInterface $url,
    ): bool {
        return $this->baseUrl === null || self::origin($url) === self::origin($this->baseUrl);
    }

    /**
     * @throws HttpClientException
     */
    private function authenticate(
        ClientRequestInterface $request,
    ): ClientRequestInterface {
        $httpClient = $this->withAuth();

        foreach ($this->auth as $auth) {
            $request = $auth->apply($request, $httpClient);
        }

        return $request;
    }

    /**
     * @throws HttpClientException
     */
    private function refresh(
        ClientResponseInterface $response,
    ): bool {
        $httpClient = $this->withAuth();
        $refreshed = false;

        foreach ($this->auth as $auth) {
            if ($auth instanceof RefreshableAuthInterface && $auth->refresh($response, $httpClient)) {
                $refreshed = true;
            }
        }

        return $refreshed;
    }

    private function withCookies(
        ClientRequestInterface $request,
    ): ClientRequestInterface {
        $cookies = $this->cookieJar?->header($request->url);

        if ($cookies === null) {
            return $request;
        }

        foreach ($request->headers as $header) {
            if ($header->is('Cookie')) {
                $cookies = $header->value . '; ' . $cookies;
            }
        }

        return $request->withHeader(
            header: new Header('Cookie', $cookies),
            replace: true,
        );
    }

    /**
     * @throws HttpClientException
     */
    private function redirect(
        ClientRequestInterface $request,
        ClientResponseInterface $response,
        string $origin,
        int $redirects,
    ): ?ClientRequestInterface {
        if (
            !\in_array($response->statusCode, self::REDIRECT_STATUSES, true) ||
            !$response->headers->has('Location') ||
            $this->redirects->max === 0
        ) {
            return null;
        }

        if ($redirects >= $this->redirects->max) {
            throw HttpClientException::fromTooManyRedirects(
                request: $request,
                maximum: $this->redirects->max,
            );
        }

        try {
            $url = $request->url->resolve($response->headers->string('Location'));
        } catch (HttpException|UrlException) {
            return null;
        }

        if (
            ($request->url->scheme === 'https' && $url->scheme !== 'https') ||
            ($url->scheme !== 'http' && $url->scheme !== 'https')
        ) {
            return null;
        }

        $crossOrigin = self::origin($url) !== $origin;

        if ($this->strictBaseUrl && !$this->sameOrigin($url)) {
            throw HttpClientException::fromForeignOrigin($request->withUrl($url));
        }

        if ($crossOrigin && !$this->redirects->followCrossOrigin) {
            return null;
        }

        $next = $request->withUrl($url);

        if (
            $response->statusCode === 303 ||
            (
                \in_array($response->statusCode, self::POST_TO_GET_STATUSES, true) &&
                $request->method === Method::POST
            )
        ) {
            $next = $next
                ->withMethod(
                    $request->method === Method::HEAD
                        ? Method::HEAD
                        : Method::GET,
                )
                ->withBody(null)
                ->withoutHeader('Content-Type')
                ->withoutHeader('Content-Length');
        } elseif (!self::replayable($request)) {
            throw HttpClientException::fromUnrewindableBody($request);
        }

        if ($crossOrigin && !$this->redirects->keepCredentialsAcrossOrigins) {
            foreach (self::CREDENTIAL_HEADERS as $name) {
                $next = $next->withoutHeader($name);
            }
        }

        if ($this->cookieJar === null) {
            return $next;
        }

        return $this->withCookies(
            $next->withoutHeader('Cookie'),
        );
    }

    private function retryPolicy(
        ClientRequestInterface $request,
        int $attempt,
        ?ClientResponseInterface $response,
    ): ?RetryPolicyInterface {
        if ($this->retry === null || $attempt >= $this->retry->times) {
            return null;
        }

        if (
            !$this->retry->retryNonIdempotent &&
            !\in_array($request->method->name, self::IDEMPOTENT_METHODS, true)
        ) {
            return null;
        }

        if ($response !== null && !\in_array($response->statusCode, $this->retry->statuses, true)) {
            return null;
        }

        return self::replayable($request)
            ? $this->retry
            : null;
    }

    private static function delay(
        RetryPolicyInterface $retry,
        int $attempt,
        ?ClientResponseInterface $response,
    ): int {
        $delay = self::microseconds($retry->delay) * ($retry->multiplier ** $attempt);
        $retryAfter = $response !== null && $response->headers->has('Retry-After')
            ? self::retryAfter($response->headers->string('Retry-After'))
            : null;

        if ($retryAfter !== null) {
            $delay = $retryAfter;
        }

        return (int) \min($delay, self::microseconds($retry->maxDelay));
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $as
     * @param list<string>|null $only
     * @return T
     *
     * @throws HttpClientException
     * @throws SerializerException
     */
    private function decode(
        ClientRequestInterface $request,
        ClientResponseInterface $response,
        string $as,
        ?string $key,
        ?array $only,
    ): object {
        $decoder = $this->decoder($request, $response);

        try {
            return $decoder->map(
                body: $response->body,
                className: $as,
                key: $key,
                only: $only,
                skipUnknownKeys: $this->skipUnknownKeys,
                skipValidation: $this->skipValidation,
            );
        } catch (ValidationException $exception) {
            throw HttpClientException::fromInvalidBody($request, $response, $exception);
        } catch (SerializerInputException|HttpException|\JsonException $exception) {
            throw HttpClientException::fromMalformedBody($request, $response, $exception);
        }
    }

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
    private function decodeList(
        ClientRequestInterface $request,
        ClientResponseInterface $response,
        string $as,
        ?string $key,
        ?array $only,
    ): array {
        $decoder = $this->decoder($request, $response);

        try {
            return $decoder->mapList(
                body: $response->body,
                className: $as,
                key: $key,
                only: $only,
                skipUnknownKeys: $this->skipUnknownKeys,
                skipValidation: $this->skipValidation,
            );
        } catch (ValidationException $exception) {
            throw HttpClientException::fromInvalidBody($request, $response, $exception);
        } catch (SerializerInputException|HttpException|\JsonException $exception) {
            throw HttpClientException::fromMalformedBody($request, $response, $exception);
        }
    }

    /**
     * @throws HttpClientException
     */
    private function decoder(
        ClientRequestInterface $request,
        ClientResponseInterface $response,
    ): ResponseDecoderInterface {
        if ($response->statusCode < 200 || $response->statusCode > 299) {
            throw HttpClientException::fromUnexpectedStatus($request, $response);
        }

        if ($response->statusCode === 204 || $response->body->getRaw() === '') {
            throw HttpClientException::fromEmptyBody($request, $response);
        }

        if ($this->decoder !== null) {
            return $this->decoder;
        }

        foreach (
            [
                new JsonDecoder(),
                new XmlDecoder(),
            ] as $decoder
        ) {
            if ($decoder->supports($response->body)) {
                return $decoder;
            }
        }

        throw HttpClientException::fromUnsupportedContentType($request, $response);
    }

    /**
     * @param (\Closure(ClientProgressInterface): mixed)|null $progress
     */
    private function transportOptions(
        bool $streaming = false,
        ?string $sink = null,
        ?int $maxSize = null,
        ?\Closure $progress = null,
    ): TransportOptionsInterface {
        return new TransportOptions(
            connectTimeout: $this->connectTimeout,
            timeout: $streaming || $sink !== null
                ? Duration::fromSeconds(0)
                : $this->timeout,
            idleTimeout: $this->idleTimeout,
            maxResponseSize: match (true) {
                $sink !== null => $maxSize,
                $streaming => null,
                default => $this->maxResponseSize,
            },
            httpVersion: $this->httpVersion,
            proxy: $this->proxy,
            clientCertificate: $this->clientCertificate,
            certificateAuthority: $this->certificateAuthority,
            addressCheck: \sizeof($this->rules) > 0
                ? $this->allowsAddress(...)
                : null,
            progress: $progress ?? $this->progress,
            sink: $sink,
        );
    }

    private function allowsAddress(
        string $address,
    ): bool {
        foreach ($this->rules as $rules) {
            if (!$rules->allowsAddress($address)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<array-key, mixed> $query
     */
    private function encodeQuery(
        array $query,
    ): string {
        $pairs = [];

        foreach ($query as $name => $value) {
            $this->appendQuery($pairs, (string) $name, $value);
        }

        return \join('&', $pairs);
    }

    /**
     * @param list<string> $pairs
     */
    private function appendQuery(
        array &$pairs,
        string $name,
        mixed $value,
    ): void {
        if ($value === null) {
            return;
        }

        if (\is_array($value)) {
            foreach ($value as $key => $item) {
                $this->appendQuery(
                    $pairs,
                    match (true) {
                        !\array_is_list($value) || \is_array($item) => $name . '[' . $key . ']',
                        $this->bracketQueryArrays => $name . '[]',
                        default => $name,
                    },
                    $item,
                );
            }

            return;
        }

        $pairs[] = \rawurlencode($name) . '=' . \rawurlencode(
            match (true) {
                $value === true => '1',
                $value === false => '0',
                \is_scalar($value) => (string) $value,
                $value instanceof \BackedEnum => (string) $value->value,
                $value instanceof \UnitEnum => $value->name,
                $value instanceof \Stringable => (string) $value,
                default => '',
            },
        );
    }

    private static function join(
        UrlInterface $baseUrl,
        UrlInterface $url,
    ): UrlInterface {
        if ($url->path === '') {
            return $baseUrl
                ->withQuery($url->query)
                ->withFragment($url->fragment);
        }

        return $baseUrl
            ->append($url->path)
            ->withQuery($url->query)
            ->withFragment($url->fragment);
    }

    private static function origin(
        UrlInterface $url,
    ): string {
        return \strtolower($url->scheme . '://' . $url->host . ':' . ($url->port ?? ($url->scheme === 'http' ? 80 : 443)));
    }

    private static function replayable(
        ClientRequestInterface $request,
    ): bool {
        return !$request->body instanceof RawBody ||
            !$request->body->body instanceof StreamInterface ||
            $request->body->body instanceof ResourceStreamInterface;
    }

    private static function retryAfter(
        string $value,
    ): ?int {
        if (\preg_match('/^\d+$/', \trim($value)) === 1) {
            return (int) \trim($value) * 1_000_000;
        }

        $date = \DateTimeImmutable::createFromFormat(
            format: 'D, d M Y H:i:s \G\M\T',
            datetime: \trim($value),
            timezone: new \DateTimeZone('UTC'),
        );

        if ($date === false) {
            return null;
        }

        return \max(0, $date->getTimestamp() - \time()) * 1_000_000;
    }

    private static function microseconds(
        DurationInterface $duration,
    ): int {
        return $duration->seconds * 1_000_000 + \intdiv($duration->nanoseconds, 1_000);
    }

    /**
     * @param HeaderInterface[] $headers
     * @return list<HeaderInterface>
     */
    private static function withoutNamed(
        array $headers,
        string $name,
    ): array {
        $remaining = [];

        foreach ($headers as $header) {
            if (!$header->is($name)) {
                $remaining[] = $header;
            }
        }

        return $remaining;
    }
}
