<?php

declare(strict_types=1);

namespace
{
    use Tuxxedo\Application\ApplicationFactory;
    use Tuxxedo\Application\ErrorHandlerInterface;
    use Tuxxedo\Container\Container;
    use Tuxxedo\Http\Request\RequestInterface;
    use Tuxxedo\Http\Response\Response;
    use Tuxxedo\Http\Response\ResponseEmitterInterface;
    use Tuxxedo\Http\Response\ResponseInterface;
    use Tuxxedo\Middleware\MiddlewareInterface;

    require_once __DIR__ . '/../vendor/autoload.php';

    class M1 implements MiddlewareInterface
    {
        public function handle(
            Container $container,
            RequestInterface $request,
        ): void {
            $emitter = $container->resolve(ResponseEmitterInterface::class);

            $emitter->emit(
                response: new Response(body: static::class),
                sendHeaders: $emitter->isSent(),
            );
        }
    }

    class M2 extends M1
    {
        public function handle(
            Container $container,
            RequestInterface $request,
        ): void {
            parent::handle($container, $request);
        }
    }

    class M3 implements MiddlewareInterface
    {
        public function handle(
            Container $container,
            RequestInterface $request,
        ): void {
            throw new Exception('Always throws');
        }
    }

    $app = ApplicationFactory::createFromDirectory(
        directory: __DIR__ . '/../app',
    );

    $app->middleware(new M1());
    $app->middleware(static fn(): MiddlewareInterface => new M2());
    // $app->middleware(new M3());

    $app->defaultExceptionHandler(
        new class implements ErrorHandlerInterface {
            public function handle(
                RequestInterface $request,
                \Throwable $exception,
            ): void {
                printf(
                    "[Default handler] Caught exception %s[%s]: %s\n",
                    $exception::class,
                    $exception->getCode(),
                    $exception->getMessage(),
                );
            }
        },
    );

    $app->whenException(
        Exception::class,
        new class implements ErrorHandlerInterface {
            public function handle(
                RequestInterface $request,
                \Throwable $exception,
            ): void {
                printf(
                    "[Specific handler] Caught exception %s[%s]: %s\n",
                    $exception::class,
                    $exception->getCode(),
                    $exception->getMessage(),
                );
            }
        },
    );

    $response = new Response(
        body: 'Hello World',
    );

    $app->run(response: $response);
}
