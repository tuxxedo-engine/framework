<?php

declare(strict_types=1);

namespace
{

    use App\Controllers\IndexController;
    use App\Middleware\M3;
    use App\Services\Logger\Logger;
    use App\Services\Logger\LoggerInterface;
    use Tuxxedo\Application\ApplicationFactory;
    use Tuxxedo\Application\ErrorHandlerInterface;
    use Tuxxedo\Container\Container;
    use Tuxxedo\Http\Method;
    use Tuxxedo\Http\Request\Handler\RequestHandlerInterface;
    use Tuxxedo\Http\Request\RequestInterface;
    use Tuxxedo\Http\Response\ResponseInterface;
    use Tuxxedo\Mapper\Mapper;
    use Tuxxedo\Router\Route;
    use Tuxxedo\Router\RouterFactory;
    use Tuxxedo\Router\StaticRouter;

    require_once __DIR__ . '/../vendor/autoload.php';

    class M1 implements RequestHandlerInterface
    {
        public function __construct(
            protected readonly Container $container,
        ) {
        }

        public function handle(
            RequestInterface $request,
            RequestHandlerInterface $next,
        ): ResponseInterface {
            $this->container->resolve(LoggerInterface::class)->log(
                entry: \sprintf(
                    'Middleware: %s',
                    static::class,
                ),
            );

            return $next->handle($request, $next);
        }
    }

    class M2 extends M1
    {
        public function handle(
            RequestInterface $request,
            RequestHandlerInterface $next,
        ): ResponseInterface {
            return parent::handle($request, $next);
        }
    }

    $app = ApplicationFactory::createFromDirectory(
        directory: __DIR__ . '/../app',
    );

    $app->middleware(new M1($app->container));
    $app->middleware(new M2($app->container));

    $app->defaultExceptionHandler(
        new class ($app->container) implements ErrorHandlerInterface {
            public function __construct(
                private readonly Container $container,
            ) {
            }

            public function handle(
                RequestInterface $request,
                \Throwable $exception,
            ): void {
                echo '<h2>Exception</h2>';
                echo '<pre>';
                echo $exception;
                echo '</pre>';

                echo '<h2>Logger</h2>';
                echo '<pre>';
                echo $this->container->resolve(LoggerInterface::class)->formatEntries();
                echo '</pre>';
            }
        },
    );

    $app->whenException(
        Exception::class,
        new class ($app->container) implements ErrorHandlerInterface {
            public function __construct(
                private readonly Container $container,
            ) {
            }

            public function handle(
                RequestInterface $request,
                \Throwable $exception,
            ): void {
                $this->container->resolve(
                    LoggerInterface::class,
                )->log(
                    entry: sprintf(
                        'Caught exception %s[%s]: %s',
                        $exception::class,
                        $exception->getCode(),
                        $exception->getMessage(),
                    ),
                );
            }
        },
    );

    $app->container->persistent(Logger::class);
    $app->container->persistent(Mapper::class);

    $app->container->persistent(
        new StaticRouter(
            routes: [
                new Route(
                    method: Method::GET,
                    uri: '/',
                    controller: IndexController::class,
                    action: 'index',
                    middleware: [
                        static fn (): RequestHandlerInterface => new M3($app->container),
                    ],
                ),
                new Route(
                    method: Method::GET,
                    uri: '/map',
                    controller: IndexController::class,
                    action: 'map',
                ),
            ],
        ),
    );

    $app->run();
}
