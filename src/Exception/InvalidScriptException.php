<?php

declare(strict_types=1);

namespace Contenir\Consent\Exception;

use InvalidArgumentException;

/**
 * Thrown when a script couldn't be written safely: no code or src, or inline
 * code that would close its own script element.
 *
 * @api
 */
final class InvalidScriptException extends InvalidArgumentException implements ExceptionInterface
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
