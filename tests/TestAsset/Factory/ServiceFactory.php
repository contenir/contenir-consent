<?php

declare(strict_types=1);

namespace Contenir\Consent\Tests\TestAsset\Factory;

use Contenir\Consent\Category;
use Contenir\Consent\Service;

/**
 * Builds services, and a site's config for them, for tests.
 */
final class ServiceFactory
{
    /**
     * A service with the values given, and the rest made up.
     *
     * @param list<string> $cookies
     */
    public static function make(
        string $key = 'video',
        string $name = 'Video',
        Category $category = Category::Preferences,
        array $cookies = ['video_id'],
        ?string $privacyUrl = null,
    ): Service {
        return new Service($key, $name, 'Video Ltd', $category, $cookies, $privacyUrl);
    }

    /**
     * YouTube as a site's config declares it.
     *
     * @return array<string, mixed>
     */
    public static function youtubeConfig(array $values = []): array
    {
        return [
            'name'        => 'YouTube',
            'provider'    => 'Google',
            'category'    => 'preferences',
            'cookies'     => ['YSC', 'VISITOR_INFO1_LIVE'],
            'privacy_url' => 'https://policies.google.com/privacy',
            ...$values,
        ];
    }
}
