<?php

declare(strict_types=1);

namespace Contenir\Consent\Internal;

use function json_encode;

use const JSON_HEX_AMP;
use const JSON_HEX_APOS;
use const JSON_HEX_QUOT;
use const JSON_HEX_TAG;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Writes PHP values as JavaScript literals that are safe inside a script
 * element: <, >, &, ' and " are escaped, so no value can end the element.
 *
 * @internal
 */
final class Js
{
    /**
     * @param scalar|array<array-key, mixed>|null $value
     */
    public static function value(string|int|bool|float|array|null $value): string
    {
        return json_encode(
            $value,
            JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_THROW_ON_ERROR,
        );
    }
}
