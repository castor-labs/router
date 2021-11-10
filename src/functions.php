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

namespace Castor\Http\Router;

use Psr\Http\Message\ServerRequestInterface as PsrRequest;

const PATH_ATTR = 'castor.router.path';
const ALLOWED_METHODS_ATTR = 'castor.router.allowed_methods';
const PARAMS_ATTR = 'castor.router.params';

function params(PsrRequest $request): array
{
    return $request->getAttribute(PARAMS_ATTR) ?? [];
}

namespace Castor\Http;

const METHOD_GET = 'GET';
const METHOD_POST = 'POST';
const METHOD_PUT = 'PUT';
const METHOD_PATCH = 'PATCH';
const METHOD_DELETE = 'DELETE';
const METHOD_HEAD = 'HEAD';
const METHOD_OPTIONS = 'OPTIONS';
