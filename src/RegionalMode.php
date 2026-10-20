<?php

declare(strict_types=1);

namespace Contenir\Consent;

use function in_array;

/**
 * Who is asked before anything that needs consent runs (CON-1).
 *
 * - OptIn: everyone. Nothing but necessary scripts runs until the visitor
 *   chooses. The default.
 * - ByCountry: visitors from the EEA, the UK and Switzerland, found from a
 *   country header such as Cloudflare's CF-IPCountry. Everyone else gets a
 *   notice. A visitor whose country isn't known is asked.
 * - Notice: nobody. Scripts run at once, and the banner tells visitors so,
 *   with the same choices to reject them or pick.
 *
 * @api
 */
enum RegionalMode: string
{
    case ByCountry = 'by-country';
    case Notice    = 'notice';
    case OptIn     = 'opt-in';

    /**
     * Countries whose law asks for consent first: the EU, the rest of the
     * EEA (Iceland, Liechtenstein, Norway), the UK and Switzerland, as ISO
     * 3166-1 codes.
     */
    public const array OPT_IN_COUNTRIES = [
        'AT',
        'BE',
        'BG',
        'CH',
        'CY',
        'CZ',
        'DE',
        'DK',
        'EE',
        'ES',
        'FI',
        'FR',
        'GB',
        'GR',
        'HR',
        'HU',
        'IE',
        'IS',
        'IT',
        'LI',
        'LT',
        'LU',
        'LV',
        'MT',
        'NL',
        'NO',
        'PL',
        'PT',
        'RO',
        'SE',
        'SI',
        'SK',
    ];

    /**
     * Whether a visitor from $country must choose before anything that
     * needs consent runs; null when the country isn't known.
     */
    public function asksFirst(?Country $country): bool
    {
        return match ($this) {
            self::ByCountry => null === $country || in_array($country->code, self::OPT_IN_COUNTRIES, strict: true),
            self::Notice => false,
            self::OptIn => true,
        };
    }

    /**
     * The mode's name in the settings.
     */
    public function label(): string
    {
        return match ($this) {
            self::ByCountry => 'Ask visitors from the EEA, UK and Switzerland; tell everyone else',
            self::Notice => 'Tell visitors, without asking first',
            self::OptIn => 'Ask everyone first',
        };
    }
}
