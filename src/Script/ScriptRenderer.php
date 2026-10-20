<?php

declare(strict_types=1);

namespace Contenir\Consent\Script;

use Contenir\Consent\Category;

use function array_filter;
use function array_keys;
use function array_map;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_string;
use function preg_match;
use function sprintf;
use function str_starts_with;

use const ARRAY_FILTER_USE_BOTH;
use const ENT_HTML5;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * Writes scripts as HTML, one element a line, with the page's CSP nonce.
 * While consent gates scripts, any script that isn't necessary is written
 * inert, as type="text/plain" with its data-category, and the banner
 * switches it on once the visitor allows its category. That is how the
 * add-ons' scripts for public pages (P14) and a site's own embeds wait for
 * consent.
 *
 * @api
 */
final readonly class ScriptRenderer
{
    /**
     * Attributes the renderer writes itself, which a script can't override.
     * Event handler attributes (on…) are dropped too: they are inline code
     * the nonce doesn't cover.
     */
    private const array RESERVED = ['data-category', 'nonce', 'src', 'type'];

    /**
     * @param bool $gate Whether scripts wait for consent: true while the site has a banner
     */
    public function __construct(
        private bool $gate = true,
    ) {}

    /**
     * @param list<Script> $scripts
     * @param ?string      $nonce   The page's CSP nonce
     */
    public function render(array $scripts, ?string $nonce = null): string
    {
        return implode("\n", array_map(fn(Script $script): string => $this->script($script, $nonce), $scripts));
    }

    /**
     * One script as an element.
     *
     * @param ?string $nonce The page's CSP nonce
     */
    public function script(Script $script, ?string $nonce = null): string
    {
        $tag = '<script';
        if ($this->gate && Category::Necessary !== $script->category) {
            $tag .= sprintf(' type="text/plain" data-category="%s"', $script->category->value);
        }

        if (null !== $script->src) {
            $tag .= sprintf(' src="%s"', $this->escape($script->src));
        }

        if (null !== $nonce && '' !== $nonce) {
            $tag .= sprintf(' nonce="%s"', $this->escape($nonce));
        }

        return $tag . $this->attributes($script->attributes) . '>' . ($script->inline ?? '') . '</script>';
    }

    /**
     * @param array<string, string|bool> $attributes
     */
    private function attributes(array $attributes): string
    {
        $allowed = array_filter(
            $attributes,
            static fn(string|bool $value, string $name): bool => (
                false !== $value
                && ! in_array($name, self::RESERVED, strict: true)
                && ! str_starts_with($name, 'on')
                && 1 === preg_match('/^[a-z][a-z0-9-]*$/D', $name)
            ),
            ARRAY_FILTER_USE_BOTH,
        );

        return implode('', array_map(
            fn(string $name, string|bool $value): string => is_string($value)
                ? sprintf(' %s="%s"', $name, $this->escape($value))
                : " {$name}",
            array_keys($allowed),
            $allowed,
        ));
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, encoding: 'UTF-8');
    }
}
