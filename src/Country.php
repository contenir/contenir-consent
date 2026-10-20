<?php

declare(strict_types=1);

namespace Contenir\Consent;

use function ctype_upper;
use function strlen;
use function strtoupper;
use function trim;

/**
 * A visitor's country, as a country header names it: an ISO 3166-1 alpha-2
 * code such as GB or AU.
 *
 * @api
 */
final readonly class Country
{
    private const int LENGTH = 2;

    /**
     * What Cloudflare sends when it doesn't know the country.
     */
    private const string UNKNOWN = 'XX';

    private function __construct(
        public string $code,
    ) {}

    /**
     * The country a header names, or null when it is missing, unknown (XX),
     * Tor (T1) or not a country code.
     */
    public static function fromHeader(?string $value): ?self
    {
        $code = strtoupper(trim($value ?? ''));
        if (self::LENGTH !== strlen($code) || ! ctype_upper($code) || self::UNKNOWN === $code) {
            return null;
        }

        return new self($code);
    }
}
