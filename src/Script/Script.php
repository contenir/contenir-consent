<?php

declare(strict_types=1);

namespace Contenir\Consent\Script;

use Contenir\Consent\Category;
use Contenir\Consent\Exception\InvalidScriptException;

use function stripos;

/**
 * A script for a page, loaded from a URL or written inline, and the consent
 * it needs before it runs.
 *
 * @api
 */
final readonly class Script
{
    /**
     * @param array<string, string|bool> $attributes Extra attributes such as async; true writes the bare name
     */
    private function __construct(
        public Category $category,
        public ?string $src,
        public ?string $inline,
        public array $attributes,
    ) {}

    /**
     * @param array<string, string|bool> $attributes
     *
     * @throws InvalidScriptException When $code is empty or would close its script element.
     */
    public static function inline(string $code, Category $category, array $attributes = []): self
    {
        if ('' === $code) {
            throw InvalidScriptException::because('An inline script needs code');
        }

        if (false !== stripos($code, needle: '</script')) {
            throw InvalidScriptException::because('An inline script may not contain "</script"');
        }

        return new self($category, null, $code, $attributes);
    }

    /**
     * @param array<string, string|bool> $attributes
     *
     * @throws InvalidScriptException When $src is empty.
     */
    public static function src(string $src, Category $category, array $attributes = []): self
    {
        if ('' === $src) {
            throw InvalidScriptException::because('A script loaded from a URL needs its src');
        }

        return new self($category, $src, null, $attributes);
    }
}
