<?php

declare(strict_types=1);

namespace Contenir\Consent\Tests\Unit;

use Contenir\Consent\ConsentConfig;
use Contenir\Consent\Country;
use Contenir\Consent\Exception\InvalidServiceException;
use Contenir\Consent\GoogleConsentMode;
use Contenir\Consent\RegionalMode;
use Contenir\Consent\SettingKeys;
use Contenir\Consent\Tests\TestAsset\Factory\ServiceFactory;
use Contenir\Consent\Tests\TestAsset\Factory\SettingsFactory;
use Contenir\Consent\Texts;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ConsentConfigTest extends TestCase
{
    /**
     * @return array<string, array{string, ?string}>
     */
    public static function privacyUrls(): array
    {
        return [
            'a path'        => ['/privacy', '/privacy'],
            'an address'    => ['https://example.com/privacy', 'https://example.com/privacy'],
            'blank'         => ['', null],
            'a script link' => ['javascript:alert(1)', null],
        ];
    }

    /**
     * @return array<string, array{mixed, int}>
     */
    public static function revisions(): array
    {
        return [
            'a number'          => [3, 3],
            'a numeric string'  => [' 12 ', 12],
            'zero'              => ['0', 0],
            'negative'          => [-2, 0],
            'a negative string' => ['-2', 0],
            'a fraction'        => ['1.5', 0],
            'text'              => ['two', 0],
            'blank'             => ['', 0],
            'a list'            => [[1], 0],
        ];
    }

    #[Test]
    public function asksEveryoneFirstInBasicModeByDefault(): void
    {
        $config = ConsentConfig::fromSettings([]);

        static::assertEquals(
            [RegionalMode::OptIn, GoogleConsentMode::Basic, 0, new Texts(), 'CF-IPCountry', null],
            [
                $config->regionalMode,
                $config->googleConsentMode,
                $config->revision,
                $config->texts,
                $config->countryHeader,
                $config->privacyUrl,
            ],
        );
    }

    #[Test]
    public function asksFirstAsItsRegionalModeSays(): void
    {
        $config = new ConsentConfig(regionalMode: RegionalMode::ByCountry);

        static::assertSame(
            [true, false],
            [$config->asksFirst(Country::fromHeader('FR')), $config->asksFirst(Country::fromHeader('AU'))],
        );
    }

    #[Test]
    public function fallsBackToTheDefaultsForValuesItCannotUse(): void
    {
        $config = ConsentConfig::fromSettings(SettingsFactory::make([
            SettingKeys::REGIONAL_MODE       => 'everywhere',
            SettingKeys::GOOGLE_CONSENT_MODE => 'full',
            SettingKeys::TITLE               => ['Cookies'],
            SettingKeys::DESCRIPTION         => '   ',
            SettingKeys::COUNTRY_HEADER      => '',
        ]));

        static::assertSame(
            [RegionalMode::OptIn, GoogleConsentMode::Basic, Texts::TITLE, Texts::DESCRIPTION, 'CF-IPCountry'],
            [
                $config->regionalMode,
                $config->googleConsentMode,
                $config->texts->title,
                $config->texts->description,
                $config->countryHeader,
            ],
        );
    }

    #[Test]
    #[DataProvider('privacyUrls')]
    public function linksOnlyToAPrivacyPolicyItCanSafelyLinkTo(string $stored, ?string $url): void
    {
        $config = ConsentConfig::fromSettings(SettingsFactory::make([SettingKeys::PRIVACY_URL => $stored]));

        static::assertSame($url, $config->privacyUrl);
    }

    #[Test]
    public function listsTheTrackingServicesAndTheSitesOwn(): void
    {
        $config = ConsentConfig::fromSettings(
            SettingsFactory::make(['facebook.analytics.key' => '123456789012345']),
            ['youtube' => ServiceFactory::youtubeConfig()],
        );

        static::assertSame(
            ['consent', 'meta-pixel', 'youtube'],
            [
                $config->services->services[0]->key,
                $config->services->services[1]->key,
                $config->services->services[2]->key,
            ],
        );
    }

    #[Test]
    public function readsEachValueFromTheSettings(): void
    {
        $config = ConsentConfig::fromSettings(SettingsFactory::make([
            SettingKeys::REGIONAL_MODE       => 'by-country',
            SettingKeys::GOOGLE_CONSENT_MODE => 'advanced',
            SettingKeys::REVISION            => '4',
            SettingKeys::TITLE               => ' Cookies here ',
            SettingKeys::DESCRIPTION         => 'We would like to count visits.',
            SettingKeys::COUNTRY_HEADER      => 'CloudFront-Viewer-Country',
            SettingKeys::PRIVACY_URL         => '/privacy',
        ]));

        static::assertSame(
            [
                RegionalMode::ByCountry,
                GoogleConsentMode::Advanced,
                4,
                'Cookies here',
                'We would like to count visits.',
                'CloudFront-Viewer-Country',
                '/privacy',
            ],
            [
                $config->regionalMode,
                $config->googleConsentMode,
                $config->revision,
                $config->texts->title,
                $config->texts->description,
                $config->countryHeader,
                $config->privacyUrl,
            ],
        );
    }

    #[Test]
    #[DataProvider('revisions')]
    public function readsTheRevisionAsAWholeNumber(mixed $stored, int $revision): void
    {
        $config = ConsentConfig::fromSettings(SettingsFactory::make([SettingKeys::REVISION => $stored]));

        static::assertSame($revision, $config->revision);
    }

    #[Test]
    public function refusesAServiceTheSiteDeclaresBadly(): void
    {
        $this->expectException(InvalidServiceException::class);

        ConsentConfig::fromSettings([], ['youtube' => ServiceFactory::youtubeConfig(['category' => 'social'])]);
    }

    #[Test]
    public function startsAtRevisionZero(): void
    {
        static::assertSame(0, (new ConsentConfig())->revision);
    }

    #[Test]
    public function startsFromTheTextsASiteGivesAndReplacesOnlyTheTitleAndDescription(): void
    {
        $texts = new Texts(
            title: 'Cookies?',
            description: 'Some text.',
            acceptAll: 'Yes',
            rejectAll: 'No',
            preferences: 'Choose',
            preferencesTitle: 'Your choices',
            preferencesIntro: 'Pick some.',
            savePreferences: 'Keep',
            close: 'Shut',
            privacyPolicy: 'Privacy',
            revised: 'Changed.',
            service: 'What',
            provider: 'Who',
            cookies: 'Crumbs',
            preferencesLink: 'Cookies',
            language: 'en-AU',
        );
        $config = ConsentConfig::fromSettings(
            SettingsFactory::make([SettingKeys::TITLE => 'Our cookies']),
            texts: $texts,
        );

        static::assertEquals(
            new Texts(
                title: 'Our cookies',
                description: 'Some text.',
                acceptAll: 'Yes',
                rejectAll: 'No',
                preferences: 'Choose',
                preferencesTitle: 'Your choices',
                preferencesIntro: 'Pick some.',
                savePreferences: 'Keep',
                close: 'Shut',
                privacyPolicy: 'Privacy',
                revised: 'Changed.',
                service: 'What',
                provider: 'Who',
                cookies: 'Crumbs',
                preferencesLink: 'Cookies',
                language: 'en-AU',
            ),
            $config->texts,
        );
    }
}
