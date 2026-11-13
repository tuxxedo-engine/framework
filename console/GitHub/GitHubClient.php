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

namespace Console\GitHub;

use Tuxxedo\Http\Client\Auth\BearerAuth;
use Tuxxedo\Http\Client\HttpClientInterface;
use Tuxxedo\Http\Client\PendingResponseInterface;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\Url\Url;
use Tuxxedo\Http\Url\UrlException;

class GitHubClient
{
    private readonly HttpClientInterface $http;
    private readonly string $owner;
    private readonly string $name;

    /**
     * @throws UrlException
     */
    public function __construct(
        HttpClientInterface $http,
        string $repository,
        #[\SensitiveParameter] string $token = '',
    ) {
        $http = $http
            ->withBaseUrl('https://api.github.com')
            ->withHeaders(
                [
                    new Header('Accept', 'application/vnd.github+json'),
                    new Header('X-GitHub-Api-Version', '2022-11-28'),
                ],
            );

        $this->http = $token !== ''
            ? $http->withAuth(new BearerAuth($token))
            : $http;

        [$this->owner, $this->name] = \array_pad(\explode('/', $repository, 2), 2, '');
    }

    /**
     * @return PendingResponseInterface<list<Issue>>
     *
     * @throws UrlException
     */
    public function issues(): PendingResponseInterface
    {
        return $this->http->deferred()->getList(
            url: $this->url('/repos/{owner}/{name}/issues'),
            as: Issue::class,
            query: [
                'state' => 'open',
                'sort' => 'created',
                'direction' => 'desc',
                'per_page' => 30,
            ],
        );
    }

    /**
     * @return PendingResponseInterface<list<PullRequest>>
     *
     * @throws UrlException
     */
    public function pullRequests(): PendingResponseInterface
    {
        return $this->http->deferred()->getList(
            url: $this->url('/repos/{owner}/{name}/pulls'),
            as: PullRequest::class,
            query: [
                'state' => 'open',
                'sort' => 'created',
                'direction' => 'desc',
                'per_page' => 5,
            ],
        );
    }

    /**
     * @return PendingResponseInterface<list<Commit>>
     *
     * @throws UrlException
     */
    public function commits(): PendingResponseInterface
    {
        return $this->http->deferred()->getList(
            url: $this->url('/repos/{owner}/{name}/commits'),
            as: Commit::class,
            query: [
                'per_page' => 5,
            ],
        );
    }

    /**
     * @return PendingResponseInterface<Commit>
     *
     * @throws UrlException
     */
    public function commit(
        string $sha,
    ): PendingResponseInterface {
        return $this->http->deferred()->get(
            url: $this->url('/repos/{owner}/{name}/commits/{sha}', $sha),
            as: Commit::class,
        );
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
    ): \Generator {
        return $this->http->each($pending);
    }

    /**
     * @throws UrlException
     */
    private function url(
        string $template,
        string $sha = '',
    ): Url {
        return Url::template(
            $template,
            [
                'owner' => $this->owner,
                'name' => $this->name,
                'sha' => $sha,
            ],
        );
    }
}
