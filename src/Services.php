<?php

declare(strict_types=1);

namespace Contenir\Consent;

use Contenir\Consent\Exception\InvalidServiceException;
use Contenir\Consent\Internal\SettingsArray;

use function array_filter;
use function array_values;
use function is_array;
use function is_string;
use function str_starts_with;
use function strtoupper;

/**
 * The services the preferences list, each under its category. The consent
 * cookie itself is always first. The Tracking add-on's tags are listed from
 * the codes a site has set, and a site adds its own (embeds it gates with
 * consentScript()) from its config.
 *
 * @api
 */
final readonly class Services
{
    /**
     * The settings keys the Tracking add-on keeps its tag IDs under, as
     * contenir/tracking's SettingKeys names them. Consent reads them only to
     * list the services, so neither package needs the other.
     */
    public const string GOOGLE_TAG = 'google.analytics.key';

    public const string TAG_MANAGER = 'google.tag_manager.id';

    public const string META_PIXEL = 'facebook.analytics.key';

    public const string LINKEDIN_INSIGHT = 'linkedin.insight.partner_id';

    /**
     * The name of the cookie that keeps the visitor's choice.
     */
    public const string CONSENT_COOKIE = 'cc_cookie';

    private const string GOOGLE_PRIVACY = 'https://policies.google.com/privacy';

    /**
     * @param list<Service> $services
     */
    public function __construct(
        public array $services = [],
    ) {}

    /**
     * The consent cookie, the Tracking add-on's tags the site has codes for,
     * and the services the site's config declares, by key.
     *
     * @param array<array-key, mixed> $settings The site's settings, as nested arrays
     * @param array<array-key, mixed> $declared The site's own services, by key (Service::fromArray())
     *
     * @throws InvalidServiceException When a declared service can't be used.
     *
     * @mago-expect analysis:mixed-assignment Config is untyped; each service is checked here.
     */
    public static function fromSettings(array $settings, array $declared = []): self
    {
        $services = [self::consentCookie(), ...self::tracking(new SettingsArray($settings))];
        foreach ($declared as $key => $values) {
            if (! is_string($key) || ! is_array($values)) {
                throw InvalidServiceException::because('Declare each service as an array under its key');
            }

            $services[] = Service::fromArray($key, $values);
        }

        return new self($services);
    }

    private static function consentCookie(): Service
    {
        return new Service(
            'consent',
            'Cookie choices',
            'This site',
            Category::Necessary,
            [self::CONSENT_COOKIE],
        );
    }

    /**
     * @return list<Service>
     */
    private static function tracking(SettingsArray $settings): array
    {
        $google     = strtoupper($settings->text(self::GOOGLE_TAG));
        $tagManager = '' !== $settings->text(self::TAG_MANAGER);
        $metaPixel  = '' !== $settings->text(self::META_PIXEL);
        $linkedIn   = '' !== $settings->text(self::LINKEDIN_INSIGHT);

        return array_values(array_filter([
            '' !== $google && ! str_starts_with($google, 'AW-')
                ? new Service(
                    'google-analytics',
                    'Google Analytics',
                    'Google',
                    Category::Analytics,
                    ['_ga', '_ga_*'],
                    self::GOOGLE_PRIVACY,
                )
                : null,
            str_starts_with($google, 'AW-')
                ? new Service(
                    'google-ads',
                    'Google Ads',
                    'Google',
                    Category::Marketing,
                    ['_gcl_au', '_gcl_aw'],
                    self::GOOGLE_PRIVACY,
                )
                : null,
            $tagManager
                ? new Service(
                    'google-tag-manager',
                    'Google Tag Manager',
                    'Google',
                    Category::Analytics,
                    [],
                    self::GOOGLE_PRIVACY,
                )
                : null,
            $metaPixel
                ? new Service(
                    'meta-pixel',
                    'Meta pixel',
                    'Meta',
                    Category::Marketing,
                    ['_fbp', '_fbc'],
                    'https://www.facebook.com/privacy/policy/',
                )
                : null,
            $linkedIn
                ? new Service(
                    'linkedin-insight',
                    'LinkedIn Insight Tag',
                    'LinkedIn',
                    Category::Marketing,
                    ['li_fat_id', 'li_sugr', 'bcookie', 'lidc', 'UserMatchHistory', 'AnalyticsSyncHistory'],
                    'https://www.linkedin.com/legal/privacy-policy',
                )
                : null,
        ]));
    }

    /**
     * The services under $category, in order.
     *
     * @return list<Service>
     */
    public function in(Category $category): array
    {
        return array_values(array_filter(
            $this->services,
            static fn(Service $service): bool => $category === $service->category,
        ));
    }
}
