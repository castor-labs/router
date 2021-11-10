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
use Psr\Log\LoggerInterface as PsrLogger;
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
    private ?PsrLogger $logger;
    private string $requestIdHeader;
    private bool $logClientErrors;

    public function __construct(
        PsrHandler $next,
        PsrResponseFactory $response,
        PsrLogger $logger = null,
        string $requestIdHeader = 'X-Request-Id',
        bool $logClientErrors = false
    ) {
        $this->next = $next;
        $this->response = $response;
        $this->logger = $logger;
        $this->requestIdHeader = $requestIdHeader;
        $this->logClientErrors = $logClientErrors;
    }

    public function handle(PsrRequest $request): PsrResponse
    {
        $request = $this->identifyRequest($request);

        try {
            return $this->next->handle($request);
        } catch (Throwable $e) {
            if (!$e instanceof ProtocolError) {
                $e = new ProtocolError(500, 'Internal Server Error', $e);
            }
            $this->logError($request, $e);

            return $this->createErrorResponse($request, $e);
        }
    }

    private function logError(PsrRequest $request, ProtocolError $error): void
    {
        if (null === $this->logger) {
            return;
        }

        $code = $error->getCode();

        if ($code < 500 && !$this->logClientErrors) {
            return;
        }

        $uri = (string) $request->getUri();
        $method = $request->getMethod();
        $msg = sprintf('Error %s while trying to %s %s', $code, $method, $uri);
        $id = $request->getHeaderLine($this->requestIdHeader);

        // We log the error in a error entry.
        $this->logger->error($msg, [
            'method' => $method,
            'code' => $code,
            'uri' => $uri,
            'request_id' => $id,
            'errors' => $error->toArray(),
        ]);

        // We store the payload and headers received in a debug entry.
        $this->logger->debug('Debugging information for request '.$id, [
            'request_id' => $id,
            'payload' => base64_encode((string) $request->getBody()),
            'headers' => $request->getHeaders(),
        ]);
    }

    private function createErrorResponse(PsrRequest $request, ProtocolError $error): PsrResponse
    {
        $response = $this->response->createResponse($error->getCode())
            ->withHeader($this->requestIdHeader, $request->getHeaderLine($this->requestIdHeader))
            ->withHeader('Content-Type', 'text/plain')
        ;
        $msg = sprintf('Could not %s %s', $request->getMethod(), $request->getUri());
        $response->getBody()->write($msg);

        return $response;
    }

    private function identifyRequest(PsrRequest $request): PsrRequest
    {
        $id = $request->getHeaderLine($this->requestIdHeader);
        if ('' === $id) {
            try {
                $id = bin2hex(random_bytes(16));
            } catch (\Exception $e) {
                throw new \RuntimeException('Could not generate a unique id for request', 0, $e);
            }
            $request = $request->withHeader($this->requestIdHeader, $id);
        }

        return $request;
    }
}
