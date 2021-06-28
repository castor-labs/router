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

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Throwable;

/**
 * Class ErrorHandlerMiddleware.
 */
final class ErrorHandlerMiddleware implements MiddlewareInterface
{
    private ResponseFactoryInterface $response;
    private bool $trace;

    /**
     * PlainTextErrorHandler constructor.
     */
    public function __construct(ResponseFactoryInterface $response, bool $trace = true)
    {
        $this->response = $response;
        $this->trace = $trace;
    }

    public function process(Request $request, Handler $handler): PsrResponse
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $e) {
            if (!$e instanceof ProtocolError) {
                $e = new ProtocolError(500, 'Internal Server Error', $e);
            }
            $string = $this->createErrorText($e);
            $code = (int) $e->getCode();
            $response = $this->response->createResponse($code);
            $response->getBody()->write($string);

            return $response
                ->withHeader('Content-Type', 'text/plain')
                ->withHeader('Content-Length', (string) strlen($string))
            ;
        }
    }

    private function createErrorText(ProtocolError $error): string
    {
        $message = sprintf('HTTP ERROR %s: %s', $error->getCode(), $error->getMessage()).PHP_EOL;

        if (true === $this->trace) {
            $message .= PHP_EOL;
            $message .= $error->getTraceAsString();

            while (true) {
                $error = $error->getPrevious();
                if (!$error instanceof Throwable) {
                    break;
                }
                $message .= PHP_EOL.PHP_EOL;
                $message .= sprintf(
                    '%s thrown on %s, line %s',
                    get_class($error),
                    $error->getFile(),
                    $error->getLine()
                ).PHP_EOL;
                $message .= 'Error message: '.$error->getMessage().PHP_EOL;

                $message .= $error->getTraceAsString();
            }
        }

        return $message;
    }
}
