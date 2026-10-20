<?php

declare(strict_types=1);

namespace Contenir\Consent;

use function array_filter;
use function array_keys;
use function array_map;
use function htmlspecialchars;
use function implode;
use function in_array;
use function preg_match;
use function sprintf;
use function str_starts_with;

use const ARRAY_FILTER_USE_KEY;
use const ENT_HTML5;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * A link that opens the cookie preferences, for a site's footer or privacy
 * page, so a visitor can change their choice at any time. The banner wires
 * it up when it starts.
 *
 * @api
 */
final readonly class PreferencesLink
{
    /**
     * Attributes the link writes itself, which a site can't override.
     */
    private const array RESERVED = ['data-cc', 'href'];

    public function __construct(
        private string $label = 'Cookie preferences',
    ) {}

    /**
     * @param array<string, string> $attributes Extra attributes such as class
     */
    public function html(array $attributes = []): string
    {
        $allowed = array_filter(
            $attributes,
            static fn(string $name): bool => (
                ! in_array($name, self::RESERVED, strict: true)
                && ! str_starts_with($name, 'on')
                && 1 === preg_match('/^[a-z][a-z0-9-]*$/D', $name)
            ),
            ARRAY_FILTER_USE_KEY,
        );

        return sprintf(
            '<a href="#" data-cc="%s"%s>%s</a>',
            Banner::SHOW_PREFERENCES,
            implode('', array_map(
                fn(string $name, string $value): string => sprintf(' %s="%s"', $name, $this->escape($value)),
                array_keys($allowed),
                $allowed,
            )),
            $this->escape($this->label),
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, encoding: 'UTF-8');
    }
}
