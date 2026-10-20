<?php

declare(strict_types=1);

namespace Contenir\Consent;

/**
 * A kind of cookie or script a visitor consents to. Necessary ones always
 * run; the others wait for the visitor. The values match
 * contenir/extension-api's Script\ConsentCategory and contenir/tracking's
 * Snippet\Category, so scripts from either pass straight through.
 *
 * @api
 */
enum Category: string
{
    case Analytics   = 'analytics';
    case Marketing   = 'marketing';
    case Necessary   = 'necessary';
    case Preferences = 'preferences';

    /**
     * Every category in the order the preferences list them.
     *
     * @return list<self>
     */
    public static function ordered(): array
    {
        return [self::Necessary, self::Analytics, self::Marketing, self::Preferences];
    }

    /**
     * What the preferences say the category is for.
     */
    public function description(): string
    {
        return match ($this) {
            self::Analytics => 'Counts visits and shows how the site is used, so we can improve it.',
            self::Marketing => 'Measures advertising and shows ads that suit you, on this site and others.',
            self::Necessary => 'Makes the site work and remembers your cookie choices. These are always on.',
            self::Preferences => 'Remembers your settings and shows content from other sites, such as videos and maps.',
        };
    }

    /**
     * The category's name in the preferences.
     */
    public function label(): string
    {
        return match ($this) {
            self::Analytics => 'Analytics',
            self::Marketing => 'Marketing',
            self::Necessary => 'Strictly necessary',
            self::Preferences => 'Preferences',
        };
    }
}
