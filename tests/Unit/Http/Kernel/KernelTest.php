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

namespace Unit\Http\Kernel;

use PHPUnit\Framework\TestCase;
use Support\Http\Kernel\StubDispatcher;
use Support\Http\Request\Context\StubBodyContext;
use Support\Http\Request\Context\StubHeaderContext;
use Support\Http\Request\Context\StubInputContext;
use Support\Http\Request\Context\StubServerContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Support\Http\Response\StubResponseEmitter;
use Tuxxedo\Config\Config;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Kernel\ErrorHandlerInterface;
use Tuxxedo\Http\Kernel\Kernel;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\PrefersResponseCodeInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\Route;
use Tuxxedo\Router\StaticRouter;

class KernelTest extends TestCase
{
    private function makeKernel(
        ?StubResponseEmitter $emitter = null,
        ?StubDispatcher $dispatcher = null,
        ?StaticRouter $router = null,
    ): Kernel {
        return new Kernel(
            container: new Container(),
            config: new Config(),
            emitter: $emitter ?? new StubResponseEmitter(),
            dispatcher: $dispatcher ?? new StubDispatcher(new Response()),
            router: $router ?? new StaticRouter(
                routes: [],
            ),
        );
    }

    private function makeRequest(
        Method $method = Method::GET,
        string $uri = '/test',
    ): Request {
        $server = new StubServerContext();
        $server->method = $method;
        $server->uri = $uri;

        return new Request(
            server: $server,
            headers: new StubHeaderContext(),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
        );
    }

    private function makeRouter(
        Method|null $method = Method::GET,
        string $uri = '/test',
    ): StaticRouter {
        return new StaticRouter(
            routes: [
                new Route(
                    method: $method,
                    uri: $uri,
                    controller: self::class,
                    action: 'index',
                ),
            ],
        );
    }

    public function testMiddlewareAddsClosure(): void
    {
        $kernel = $this->makeKernel();
        $closure = static fn (): MiddlewareInterface => throw new \LogicException('stub');

        $kernel->middleware($closure);

        self::assertCount(1, $kernel->middleware);
        self::assertSame($closure, $kernel->middleware[0]);
    }

    public function testMiddlewareWrapsInstanceInClosure(): void
    {
        $kernel = $this->makeKernel();

        $instance = new class () implements MiddlewareInterface {
            public function handle(
                RequestInterface $request,
                MiddlewareInterface $next,
            ): ResponseInterface {
                return $next->handle($request, $next);
            }
        };

        $kernel->middleware($instance);

        self::assertCount(1, $kernel->middleware);
        self::assertInstanceOf(\Closure::class, $kernel->middleware[0]);
        self::assertSame($instance, ($kernel->middleware[0])());
    }

    public function testMiddlewareReturnsSelf(): void
    {
        $kernel = $this->makeKernel();
        $closure = static fn (): MiddlewareInterface => throw new \LogicException('stub');

        self::assertSame($kernel, $kernel->middleware($closure));
    }

    public function testWhenExceptionRegistersHandler(): void
    {
        $kernel = $this->makeKernel();
        $closure = static fn (): ErrorHandlerInterface => throw new \LogicException('stub');

        $kernel->whenException(\RuntimeException::class, $closure);

        self::assertArrayHasKey(\RuntimeException::class, $kernel->exceptionHandlers);
        self::assertCount(1, $kernel->exceptionHandlers[\RuntimeException::class]);
    }

    public function testWhenExceptionReturnsSelf(): void
    {
        $kernel = $this->makeKernel();
        $closure = static fn (): ErrorHandlerInterface => throw new \LogicException('stub');

        self::assertSame($kernel, $kernel->whenException(\RuntimeException::class, $closure));
    }

    public function testWhenExceptionWrapsInstanceInClosure(): void
    {
        $kernel = $this->makeKernel();

        $instance = new class () implements ErrorHandlerInterface {
            public function handle(
                RequestInterface $request,
                ResponseInterface $response,
                \Throwable $exception,
            ): ResponseInterface {
                return $response;
            }
        };

        $kernel->whenException(\RuntimeException::class, $instance);

        $stored = $kernel->exceptionHandlers[\RuntimeException::class][0];

        self::assertInstanceOf(\Closure::class, $stored);
        self::assertSame($instance, $stored());
    }

    public function testDefaultExceptionHandlerRegistersHandler(): void
    {
        $kernel = $this->makeKernel();
        $closure = static fn (): ErrorHandlerInterface => throw new \LogicException('stub');

        $kernel->defaultExceptionHandler($closure);

        self::assertCount(1, $kernel->defaultExceptionHandlers);
    }

    public function testDefaultExceptionHandlerReturnsSelf(): void
    {
        $kernel = $this->makeKernel();
        $closure = static fn (): ErrorHandlerInterface => throw new \LogicException('stub');

        self::assertSame($kernel, $kernel->defaultExceptionHandler($closure));
    }

    public function testRunDispatchesRouteAndEmitsResponse(): void
    {
        $emitter = new StubResponseEmitter();
        $response = new Response(
            body: 'ok',
        );

        $kernel = $this->makeKernel(
            emitter: $emitter,
            dispatcher: new StubDispatcher($response),
            router: $this->makeRouter(),
        );

        $kernel->run($this->makeRequest());

        self::assertSame($response, $emitter->lastResponse);
    }

    public function testRunEmits404WhenRouteNotFound(): void
    {
        $emitter = new StubResponseEmitter();

        $kernel = $this->makeKernel(
            emitter: $emitter,
            router: new StaticRouter(
                routes: [],
            ),
        );

        $kernel->run($this->makeRequest());

        self::assertNotNull($emitter->lastResponse);
        self::assertSame(ResponseCode::NOT_FOUND, $emitter->lastResponse->responseCode);
    }

    public function testRunResolvesRequestFromContainerWhenNull(): void
    {
        $emitter = new StubResponseEmitter();
        $response = new Response(
            body: 'from container',
        );

        $container = new Container();

        $container->persistent(
            class: $this->makeRequest(),
        );

        $kernel = new Kernel(
            container: $container,
            config: new Config(),
            emitter: $emitter,
            dispatcher: new StubDispatcher($response),
            router: $this->makeRouter(),
        );

        $kernel->run();

        self::assertSame($response, $emitter->lastResponse);
    }

    public function testRunCallsExceptionHandlerOnDispatcherThrow(): void
    {
        $emitter = new StubResponseEmitter();

        $handler = new class () implements ErrorHandlerInterface {
            public bool $called = false;

            public function handle(
                RequestInterface $request,
                ResponseInterface $response,
                \Throwable $exception,
            ): ResponseInterface {
                $this->called = true;

                return $response;
            }
        };

        $kernel = $this->makeKernel(
            emitter: $emitter,
            dispatcher: new StubDispatcher(
                result: new \RuntimeException('test'),
            ),
            router: $this->makeRouter(),
        );

        $kernel->whenException(\RuntimeException::class, $handler);
        $kernel->run($this->makeRequest());

        self::assertTrue($handler->called);
    }

    public function testRunExecutesRegisteredMiddleware(): void
    {
        $emitter = new StubResponseEmitter();

        $middleware = new class () implements MiddlewareInterface {
            public bool $called = false;

            public function handle(
                RequestInterface $request,
                MiddlewareInterface $next,
            ): ResponseInterface {
                $this->called = true;

                return $next->handle($request, $next);
            }
        };

        $kernel = $this->makeKernel(
            emitter: $emitter,
            dispatcher: new StubDispatcher(new Response(body: 'ok')),
            router: $this->makeRouter(),
        );

        $kernel->middleware($middleware);
        $kernel->run($this->makeRequest());

        self::assertTrue($middleware->called);
        self::assertSame('ok', $emitter->lastResponse?->body);
    }

    public function testRunEmitsResponseExceptionDirectly(): void
    {
        $emitter = new StubResponseEmitter();

        $kernel = $this->makeKernel(
            emitter: $emitter,
            dispatcher: new StubDispatcher(HttpException::fromUnauthorized()),
            router: $this->makeRouter(),
        );

        $kernel->run($this->makeRequest());

        self::assertNotNull($emitter->lastResponse);
        self::assertSame(ResponseCode::UNAUTHORIZED, $emitter->lastResponse->responseCode);
    }

    public function testHandleExceptionUsesResponseCodeFromOriginalException(): void
    {
        $emitter = new StubResponseEmitter();

        $exception = new class () extends \Exception implements PrefersResponseCodeInterface {
            public ?ResponseCode $responseCode {
                get {
                    return ResponseCode::FORBIDDEN;
                }
            }
        };

        $kernel = $this->makeKernel(
            emitter: $emitter,
            dispatcher: new StubDispatcher($exception),
            router: $this->makeRouter(),
        );

        $kernel->run($this->makeRequest());

        self::assertSame(ResponseCode::FORBIDDEN, $emitter->lastResponse?->responseCode);
    }

    public function testHandleExceptionFindsResponseExceptionInChain(): void
    {
        $emitter = new StubResponseEmitter();

        $kernel = $this->makeKernel(
            emitter: $emitter,
            dispatcher: new StubDispatcher(
                new \RuntimeException(
                    message: 'outer',
                    previous: HttpException::fromUnauthorized(),
                ),
            ),
            router: $this->makeRouter(),
        );

        $kernel->run($this->makeRequest());

        self::assertSame(ResponseCode::UNAUTHORIZED, $emitter->lastResponse?->responseCode);
    }

    public function testHandleExceptionFindsPreferredResponseCodeInChain(): void
    {
        $emitter = new StubResponseEmitter();

        $inner = new class () extends \Exception implements PrefersResponseCodeInterface {
            public ?ResponseCode $responseCode {
                get {
                    return ResponseCode::NOT_ACCEPTABLE;
                }
            }
        };

        $kernel = $this->makeKernel(
            emitter: $emitter,
            dispatcher: new StubDispatcher(
                new \RuntimeException(
                    message: 'outer',
                    previous: $inner,
                ),
            ),
            router: $this->makeRouter(),
        );

        $kernel->run($this->makeRequest());

        self::assertSame(ResponseCode::NOT_ACCEPTABLE, $emitter->lastResponse?->responseCode);
    }

    public function testHandleExceptionFallsBackTo500WhenNothingMatches(): void
    {
        $emitter = new StubResponseEmitter();

        $kernel = $this->makeKernel(
            emitter: $emitter,
            dispatcher: new StubDispatcher(
                result: new \RuntimeException('plain'),
            ),
            router: $this->makeRouter(),
        );

        $kernel->run($this->makeRequest());

        self::assertSame(ResponseCode::INTERNAL_SERVER_ERROR, $emitter->lastResponse?->responseCode);
    }

    public function testDefaultExceptionHandlerWrapsInstance(): void
    {
        $kernel = $this->makeKernel();

        $instance = new class () implements ErrorHandlerInterface {
            public function handle(
                RequestInterface $request,
                ResponseInterface $response,
                \Throwable $exception,
            ): ResponseInterface {
                return $response;
            }
        };

        $kernel->defaultExceptionHandler($instance);

        self::assertCount(1, $kernel->defaultExceptionHandlers);
        self::assertInstanceOf(\Closure::class, $kernel->defaultExceptionHandlers[0]);
        self::assertSame($instance, ($kernel->defaultExceptionHandlers[0])());
    }

    public function testDefaultExceptionHandlerInstanceIsCalledOnException(): void
    {
        $emitter = new StubResponseEmitter();

        $handler = new class () implements ErrorHandlerInterface {
            public bool $called = false;

            public function handle(
                RequestInterface $request,
                ResponseInterface $response,
                \Throwable $exception,
            ): ResponseInterface {
                $this->called = true;

                return $response;
            }
        };

        $kernel = $this->makeKernel(
            emitter: $emitter,
            dispatcher: new StubDispatcher(
                result: new \RuntimeException('test'),
            ),
            router: $this->makeRouter(),
        );

        $kernel->defaultExceptionHandler($handler);
        $kernel->run($this->makeRequest());

        self::assertTrue($handler->called);
    }
}
