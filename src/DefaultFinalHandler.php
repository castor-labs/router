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

use const Castor\Http\Router\ALLOWED_METHODS_ATTR;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as PsrHandler;

/**
 * Class DefaultFinalHandler.
 */
final class DefaultFinalHandler implements PsrHandler
{
    /**
     * @throws ProtocolError
     */
    public function handle(Request $request): Response
    {
        $message = sprintf('Cannot serve %s %s:', $request->getMethod(), $request->getUri()->getPath());
        $allowedMethods = $request->getAttribute(ALLOWED_METHODS_ATTR, []);
        if ([] === $allowedMethods) {
            throw new ProtocolError(404, $message.' Path not found');
        }

        throw new ProtocolError(405, $message.' Allowed methods are '.implode(', ', $allowedMethods));
    }
}
