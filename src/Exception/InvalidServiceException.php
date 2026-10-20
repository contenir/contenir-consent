<?php

declare(strict_types=1);

namespace Contenir\Consent\Exception;

use InvalidArgumentException;

/**
 * Thrown when a service a site declares can't be listed: no key or name, or
 * a category that doesn't exist.
 *
 * @api
 */
final class InvalidServiceException extends InvalidArgumentException implements ExceptionInterface
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
