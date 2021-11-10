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

use Exception;
use Throwable;

/**
 * Class ProtocolError represents an error in the HTTP Protocol specification.
 */
final class ProtocolError extends Exception
{
    public function __construct(int $status = 500, string $message = 'Internal Server Error', Throwable $previous = null)
    {
        parent::__construct($message, $status, $previous);
    }

    public function toArray(): array
    {
        $errors = [];
        $error = $this;
        while (null !== $error) {
            $errors[] = [
                'class' => get_class($error),
                'msg' => $error->getMessage(),
                'code' => $error->getCode(),
                'line' => $error->getLine(),
                'file' => $error->getFile(),
            ];
            $error = $error->getPrevious();
        }

        return $errors;
    }
}
