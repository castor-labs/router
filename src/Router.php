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
     * @throws ProtocolError
     */
    public function handle(PsrRequest $request): PsrResponse
    {
        try {
            $handler = Frame::stack($this->fallbackHandler, ...$this->middleware);
        } catch (EmptyStackError $e) {
            throw new ProtocolError(500, 'Router does not have any handlers that can handle the request', $e);
        }

        return $handler->handle($request);
    }

    public function path(string $path): Route
    {
        $route = new Route($this, [], $path);
        $this->use($route);

        return $route;
    }

    public function method(string ...$methods): Route
    {
        $route = new Route($this, $methods);
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
