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

use function Castor\Http\Router\params;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\RequestHandlerInterface as PsrHandler;

/**
 * This Handler flattens router parameters and then calls the next handler in the chain.
 */
final class FlattenRouteParams implements PsrHandler
{
    private PsrHandler $next;

    public function __construct(PsrHandler $next)
    {
        $this->next = $next;
    }

    public static function make(PsrHandler $next): PsrHandler
    {
        return new self($next);
    }

    public function handle(PsrRequest $request): PsrResponse
    {
        $params = params($request);
        foreach ($params as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }

        return $this->next->handle($request);
    }
}
