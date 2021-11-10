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
use Psr\Http\Server\RequestHandlerInterface as PsrHandler;

/**
 * Class Frame adapts a PsrMiddleware to a PsrHandler.
 *
 * It can create a stack of Frames that runs middleware pipelines by wrapping itself recursively.
 */
final class Frame implements PsrHandler
{
    private PsrMiddleware $middleware;
    private PsrHandler $next;

    /**
     * MiddlewarePipeline constructor.
     */
    private function __construct(PsrMiddleware $middleware, PsrHandler $next)
    {
        $this->middleware = $middleware;
        $this->next = $next;
    }

    /**
     * @param PsrMiddleware ...$middleware
     *
     * @throws EmptyStackError
     */
    public static function stack(PsrHandler $handler, PsrMiddleware ...$middleware): Frame
    {
        foreach (array_reverse($middleware) as $frame) {
            $handler = new self($frame, $handler);
        }
        if (!$handler instanceof self) {
            throw new EmptyStackError(sprintf('You need at least one %s instance to create a stack', PsrMiddleware::class));
        }

        return $handler;
    }

    public function handle(PsrRequest $request): PsrResponse
    {
        return $this->middleware->process($request, $this->next);
    }
}
