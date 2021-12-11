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

use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\MiddlewareInterface as PsrMiddleware;
use Psr\Http\Server\RequestHandlerInterface as PSrHandler;

/**
 * Class Router.
 *
 * This Router is implemented as a middleware execution pipeline.
 */
class Router implements PSrHandler
{
    /**
     * @var PsrMiddleware[]
     * @psalm-var array<int,PsrMiddleware>
     */
    private array $middleware;
    private PSrHandler $fallbackHandler;

    /**
     * Router constructor.
     *
     * @param PsrMiddleware ...$middleware
     */
    public function __construct(PSrHandler $fallbackHandler = null, PsrMiddleware ...$middleware)
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
    public function use(PsrMiddleware $middleware): Router
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    /**
     * @throws EmptyStackError
     */
    public function handle(PsrRequest $request): PsrResponse
    {
        $handler = Frame::stack($this->fallbackHandler, ...$this->middleware);

        return $handler->handle($request);
    }

    /**
     * Registers a $handler to run when matching $path and GET method.
     */
    public function get(string $path, PSrHandler $handler): Route
    {
        return $this->route([METHOD_GET, METHOD_HEAD], $path, $handler);
    }

    /**
     * Registers a $handler to run when matching $path and POST method.
     */
    public function post(string $path, PSrHandler $handler): Route
    {
        return $this->route([METHOD_POST], $path, $handler);
    }

    /**
     * Registers a $handler to run when matching $path and PUT method.
     */
    public function put(string $path, PSrHandler $handler): Route
    {
        return $this->route([METHOD_PUT], $path, $handler);
    }

    /**
     * Registers a $handler to run when matching $path and PATCH method.
     */
    public function patch(string $path, PSrHandler $handler): Route
    {
        return $this->route([METHOD_PATCH], $path, $handler);
    }

    /**
     * Registers a $handler to run when matching $path and DELETE method.
     */
    public function delete(string $path, PSrHandler $handler): Route
    {
        return $this->route([METHOD_DELETE], $path, $handler);
    }

    /**
     * Registers a $handler to run when matching $path and $methods.
     */
    public function route(array $methods, string $path, PSrHandler $handler): Route
    {
        $route = new Route($handler, $methods, $path);
        $this->use($route);

        return $route;
    }

    /**
     * Registers a $handler to run when matching $path.
     */
    public function path(string $path, PSrHandler $handler): Route
    {
        $route = new Route($handler, [], $path);
        $this->use($route);

        return $route;
    }

    /**
     * Creates a new router with the same underlying fallback handler.
     */
    public function new(): Router
    {
        return new Router($this->fallbackHandler);
    }
}
