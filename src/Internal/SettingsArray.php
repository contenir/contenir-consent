<?php

declare(strict_types=1);

namespace Contenir\Consent\Internal;

use function array_key_exists;
use function ctype_digit;
use function explode;
use function is_array;
use function is_int;
use function is_string;
use function max;
use function trim;

/**
 * Reads values from a site's settings, held as nested arrays, by dotted key.
 *
 * @internal
 */
final readonly class SettingsArray
{
    /**
     * @param array<array-key, mixed> $settings
     */
    public function __construct(
        private array $settings,
    ) {}

    /**
     * The value at $key as a whole number of zero or more, or $default when
     * it is missing or can't be read as one.
     *
     * @param non-negative-int $default
     *
     * @return non-negative-int
     *
     * @mago-expect analysis:mixed-assignment Settings are untyped; the value is checked here.
     */
    public function count(string $key, int $default): int
    {
        $value = $this->find($key);
        $text  = is_string($value) || is_int($value) ? trim((string) $value) : '';
        if ('' === $text || ! ctype_digit($text)) {
            return $default;
        }

        return max(0, (int) $text);
    }

    /**
     * The value at $key as trimmed text; anything that isn't text or a
     * number is an empty string.
     *
     * @mago-expect analysis:mixed-assignment Settings are untyped; the value is checked here.
     */
    public function text(string $key): string
    {
        $value = $this->find($key);

        return is_string($value) || is_int($value) ? trim((string) $value) : '';
    }

    /**
     * @mago-expect analysis:mixed-assignment Settings are untyped; each level is checked here.
     */
    private function find(string $key): mixed
    {
        $value = $this->settings;
        foreach (explode('.', $key) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
