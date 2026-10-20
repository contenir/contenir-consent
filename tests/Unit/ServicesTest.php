<?php

declare(strict_types=1);

namespace Contenir\Consent\Tests\Unit;

use Contenir\Consent\Category;
use Contenir\Consent\Exception\InvalidServiceException;
use Contenir\Consent\Service;
use Contenir\Consent\Services;
use Contenir\Consent\Tests\TestAsset\Factory\ServiceFactory;
use Contenir\Consent\Tests\TestAsset\Factory\SettingsFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;

#[Group('unit')]
final class ServicesTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>, list<string>}>
     */
    public static function trackingCodes(): array
    {
        return [
            'no codes'              => [[], ['consent']],
            'a Google Analytics ID' => [['google.analytics.key' => 'G-ABC1234567'], ['consent', 'google-analytics']],
            'a Google tag ID'       => [['google.analytics.key' => 'GT-ABC1234'], ['consent', 'google-analytics']],
            'a Google Ads ID'       => [['google.analytics.key' => 'aw-123456789'], ['consent', 'google-ads']],
            'a container'           => [['google.tag_manager.id' => 'GTM-ABCD123'], ['consent', 'google-tag-manager']],
            'a Meta pixel'          => [['facebook.analytics.key' => '123456789012345'], ['consent', 'meta-pixel']],
            'a LinkedIn partner'    => [['linkedin.insight.partner_id' => '1234567'], ['consent', 'linkedin-insight']],
            'blank codes'           => [
                [
                    'google.analytics.key'        => ' ',
                    'google.tag_manager.id'       => '',
                    'facebook.analytics.key'      => '',
                    'linkedin.insight.partner_id' => '',
                ],
                ['consent'],
            ],
        ];
    }

    #[Test]
    public function addsTheServicesTheSiteDeclaresAfterTheTrackingOnes(): void
    {
        $services = Services::fromSettings(
            SettingsFactory::make(['google.analytics.key' => 'G-ABC1234567']),
            ['youtube' => ServiceFactory::youtubeConfig()],
        );

        static::assertSame(['consent', 'google-analytics', 'youtube'], $this->keys($services));
    }

    #[Test]
    public function describesEachTrackingService(): void
    {
        $services = Services::fromSettings(SettingsFactory::everyTrackingCode());

        static::assertEquals(
            [
                new Service('consent', 'Cookie choices', 'This site', Category::Necessary, ['cc_cookie']),
                new Service(
                    'google-analytics',
                    'Google Analytics',
                    'Google',
                    Category::Analytics,
                    ['_ga', '_ga_*'],
                    'https://policies.google.com/privacy',
                ),
                new Service(
                    'google-tag-manager',
                    'Google Tag Manager',
                    'Google',
                    Category::Analytics,
                    [],
                    'https://policies.google.com/privacy',
                ),
                new Service(
                    'meta-pixel',
                    'Meta pixel',
                    'Meta',
                    Category::Marketing,
                    ['_fbp', '_fbc'],
                    'https://www.facebook.com/privacy/policy/',
                ),
                new Service(
                    'linkedin-insight',
                    'LinkedIn Insight Tag',
                    'LinkedIn',
                    Category::Marketing,
                    ['li_fat_id', 'li_sugr', 'bcookie', 'lidc', 'UserMatchHistory', 'AnalyticsSyncHistory'],
                    'https://www.linkedin.com/legal/privacy-policy',
                ),
            ],
            $services->services,
        );
    }

    #[Test]
    public function describesGoogleAdsAsMarketing(): void
    {
        $services = Services::fromSettings(SettingsFactory::make(['google.analytics.key' => 'AW-123456789']));

        static::assertEquals(
            new Service(
                'google-ads',
                'Google Ads',
                'Google',
                Category::Marketing,
                ['_gcl_au', '_gcl_aw'],
                'https://policies.google.com/privacy',
            ),
            $services->services[1],
        );
    }

    #[Test]
    public function listsTheServicesUnderOneCategoryInOrder(): void
    {
        $services = Services::fromSettings(SettingsFactory::everyTrackingCode());

        static::assertSame(
            ['meta-pixel', 'linkedin-insight'],
            array_map(static fn(Service $service): string => $service->key, $services->in(Category::Marketing)),
        );
    }

    #[Test]
    #[DataProvider('trackingCodes')]
    public function listsTheTrackingAddOnsTagsTheSiteHasCodesFor(array $codes, array $keys): void
    {
        static::assertSame($keys, $this->keys(Services::fromSettings(SettingsFactory::make($codes))));
    }

    #[Test]
    public function refusesADeclaredServiceThatIsNotAnArray(): void
    {
        $this->expectException(InvalidServiceException::class);
        $this->expectExceptionMessage('Declare each service as an array under its key');

        Services::fromSettings([], ['youtube' => 'YouTube']);
    }

    #[Test]
    public function refusesADeclaredServiceWithoutAKey(): void
    {
        $this->expectException(InvalidServiceException::class);
        $this->expectExceptionMessage('Declare each service as an array under its key');

        Services::fromSettings([], [ServiceFactory::youtubeConfig()]);
    }

    #[Test]
    public function startsEmpty(): void
    {
        static::assertSame([], (new Services())->in(Category::Necessary));
    }

    /**
     * @return list<string>
     */
    private function keys(Services $services): array
    {
        return array_map(static fn(Service $service): string => $service->key, $services->services);
    }
}
