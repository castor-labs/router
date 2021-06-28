<?php

declare(strict_types=1);

/**
 * @project Castor Router
 * @link https://github.com/castor-labs/router
 * @package castor/router
 * @author Matias Navarro-Carter mnavarrocarter@gmail.com
 * @license MIT
 * @copyright 2021 CastorLabs Ltd
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Castor\Http;

use MNC\PathToRegExpPHP\PathRegExpFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/**
 * Class Router.
 *
 * This Router is implemented as a middleware execution pipeline.
 */
class Router implements Handler
{
    /**
     * @var Middleware[]
     * @psalm-var array<int,Middleware>
     */
    private array $middleware;
    private Handler $fallbackHandler;

    /**
     * Router constructor.
     *
     * @param Middleware ...$middleware
     */
    public function __construct(Handler $fallbackHandler = null, Middleware ...$middleware)
    {
        $this->fallbackHandler = $fallbackHandler ?? new DefaultFinalHandler();
        $this->middleware = $middleware;
    }

    public static function create(): Router
    {
        return new self();
    }

    /**
     * @return static
     */
    public function use(Middleware $middleware): Router
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    public function handle(Request $request): ResponseInterface
    {
        $handler = MiddlewareRunner::stack($this->fallbackHandler, ...$this->middleware);

        return $handler->handle($request);
    }

    /**
     * Registers $handler for $path that only handles GET requests.
     *
     * By default, also the HEAD method is added.
     *
     * @return static
     */
    public function get(string $path, Handler $handler): Router
    {
        $this->route(['GET', 'HEAD'], $path, $handler);

        return $this;
    }

    /**
     * Registers $handler for $path that only handles POST requests.
     *
     * @return static
     */
    public function post(string $path, Handler $handler): Router
    {
        $this->route(['POST'], $path, $handler);

        return $this;
    }

    /**
     * Registers $handler for $path that handles both GET and POST requests.
     *
     * This is useful when dealing with HTML web forms.
     *
     * @return static
     */
    public function form(string $path, Handler $handler): Router
    {
        $this->route(['GET', 'POST'], $path, $handler);

        return $this;
    }

    /**
     * Registers $handler for $path that only handles PUT requests.
     *
     * @return static
     */
    public function put(string $path, Handler $handler): Router
    {
        $this->route(['PUT'], $path, $handler);

        return $this;
    }

    /**
     * Registers $handler for $path that only handles PATCH requests.
     *
     * @return static
     */
    public function patch(string $path, Handler $handler): Router
    {
        $this->route(['PATCH'], $path, $handler);

        return $this;
    }

    /**
     * Registers $handler for $path that only handles DELETE requests.
     *
     * @return static
     */
    public function delete(string $path, Handler $handler): Router
    {
        $this->route(['DELETE'], $path, $handler);

        return $this;
    }

    /**
     * Registers $handler for $path that only handles OPTIONS requests.
     *
     * @return static
     */
    public function options(string $path, Handler $handler): Router
    {
        $this->route(['OPTIONS'], $path, $handler);

        return $this;
    }

    /**
     * Registers a $handler for $path with custom methods.
     *
     * @param string[] $methods
     *
     * @return static
     */
    public function route(array $methods, string $path, Handler $handler): Router
    {
        $this->use(new Route($methods, PathRegExpFactory::create($path), $handler));

        return $this;
    }

    /**
     * Mounts a handler into a path.
     *
     * When the request path matches the provided path, the request is passed
     * to that handler.
     *
     * Use this method to mount routers on top of other routers.
     *
     * @return $this
     */
    public function mount(string $path, Handler $handler): Router
    {
        return $this->use(new Path(PathRegExpFactory::create($path, 0), $handler));
    }

    /**
     * Creates a group of routes under the specified path.
     *
     * @psalm-param callable(Router): void $callback
     */
    public function group(string $path, callable $callback): Router
    {
        $router = $this->child();
        $this->mount($path, $router);

        $callback($router);

        return $this;
    }

    /**
     * Creates a new router with the same underlying fallback handler.
     */
    public function child(): Router
    {
        return new Router($this->fallbackHandler);
    }
}
