<?php

declare(strict_types=1);

namespace Contenir\Consent\Tests\TestAsset\Factory;

use function array_replace_recursive;
use function array_reverse;
use function explode;

/**
 * Builds a site's settings as nested arrays, the way settings files hold
 * them, from dotted keys.
 */
final class SettingsFactory
{
    /**
     * Every Tracking add-on code set, and the values given.
     *
     * @param array<string, mixed> $values By dotted key
     *
     * @return array<array-key, mixed>
     */
    public static function everyTrackingCode(array $values = []): array
    {
        return self::make([
            'google.analytics.key'        => 'G-ABC1234567',
            'google.tag_manager.id'       => 'GTM-ABCD123',
            'facebook.analytics.key'      => '123456789012345',
            'linkedin.insight.partner_id' => '1234567',
            ...$values,
        ]);
    }

    /**
     * @param array<string, mixed> $values By dotted key
     *
     * @return array<array-key, mixed>
     */
    public static function make(array $values): array
    {
        $settings = [];
        foreach ($values as $key => $value) {
            foreach (array_reverse(explode('.', $key)) as $segment) {
                $value = [$segment => $value];
            }
            $settings = array_replace_recursive($settings, $value);
        }

        return $settings;
    }
}
