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

use Psr\Http\Message\ResponseFactoryInterface as PsrResponseFactory;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\RequestHandlerInterface as PsrHandler;
use Throwable;

/**
 * Class HandleError wraps a PsrHandler to handle an error condition.
 *
 * It is usually used to wrap a Router instance.
 */
final class ErrorHandler implements PsrHandler
{
    private PsrHandler $next;
    private PsrResponseFactory $response;

    public function __construct(
        PsrHandler $next,
        PsrResponseFactory $response
    ) {
        $this->next = $next;
        $this->response = $response;
    }

    public function handle(PsrRequest $request): PsrResponse
    {
        try {
            return $this->next->handle($request);
        } catch (RouteNotFound $e) {
            return $this->createErrorResponse($request, 404);
        } catch (MethodNotAllowed $e) {
            return $this->createErrorResponse($request, 405);
        } catch (EmptyStackError | Throwable $e) {
            return $this->createErrorResponse($request, 500);
        }
    }

    private function createErrorResponse(PsrRequest $request, int $status): PsrResponse
    {
        $response = $this->response->createResponse($status)
            ->withHeader('Content-Type', 'text/plain')
        ;
        $msg = sprintf('Could not %s %s', $request->getMethod(), $request->getUri());
        $response->getBody()->write($msg);

        return $response;
    }
}
