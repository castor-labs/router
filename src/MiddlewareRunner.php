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

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/**
 * Class MiddlewarePipeline adapts a MiddlewareInterface to a
 * RequestHandlerInterface.
 */
final class MiddlewareRunner implements Handler
{
    private Middleware $middleware;
    private Handler $next;

    /**
     * MiddlewarePipeline constructor.
     */
    private function __construct(Middleware $middleware, Handler $next)
    {
        $this->middleware = $middleware;
        $this->next = $next;
    }

    /**
     * @param Middleware ...$middleware
     */
    public static function stack(Handler $handler, Middleware ...$middleware): MiddlewareRunner
    {
        foreach (array_reverse($middleware) as $frame) {
            $handler = new self($frame, $handler);
        }
        if (!$handler instanceof self) {
            throw new InvalidArgumentException('You need at least one $middleware to create a stack');
        }

        return $handler;
    }

    public function handle(Request $request): Response
    {
        return $this->middleware->process($request, $this->next);
    }
}
