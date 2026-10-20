<?php

declare(strict_types=1);

namespace Contenir\Consent\Tests\Integration;

use Contenir\Consent\Banner;
use Contenir\Consent\Category;
use Contenir\Consent\ConsentConfig;
use Contenir\Consent\ConsentMode;
use Contenir\Consent\Country;
use Contenir\Consent\PreferencesLink;
use Contenir\Consent\Script\Script;
use Contenir\Consent\Script\ScriptRenderer;
use Contenir\Consent\SettingKeys;
use Contenir\Consent\Tests\TestAsset\Factory\ServiceFactory;
use Contenir\Consent\Tests\TestAsset\Factory\SettingsFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function json_decode;
use function preg_match;

use const JSON_THROW_ON_ERROR;

/**
 * A site's settings, written as the parts of a page a site adapter prints.
 */
#[Group('integration')]
final class PageTest extends TestCase
{
    #[Test]
    public function startsTheBannerWithConfigurationTheBrowserCanRead(): void
    {
        $config = ConsentConfig::fromSettings(SettingsFactory::everyTrackingCode([
            SettingKeys::REGIONAL_MODE => 'by-country',
            SettingKeys::REVISION      => '3',
        ]));
        $start = (string) (new Banner($config))->scripts('/c.js', Country::fromHeader('AU'))[1]->inline;

        preg_match('/start\((.*)\);$/s', $start, $json);
        $options = json_decode($json[1] ?? '', associative: true, flags: JSON_THROW_ON_ERROR);

        static::assertSame(
            [
                'opt-out',
                3,
                [
                    'cookies' => [
                        ['name' => '_fbp'],
                        ['name' => '_fbc'],
                        ['name' => 'li_fat_id'],
                        [
                            'name' => 'li_sugr',
                        ],
                        ['name' => 'bcookie'],
                        ['name' => 'lidc'],
                        ['name' => 'UserMatchHistory'],
                        [
                            'name' => 'AnalyticsSyncHistory',
                        ],
                    ],
                ],
            ],
            [
                $options['config']['mode'],
                $options['config']['revision'],
                $options['config']['categories']['marketing']['autoClear'],
            ],
        );
    }

    #[Test]
    public function writesASiteWithoutTrackingAPageThatWaitsForConsent(): void
    {
        $config = ConsentConfig::fromSettings(
            SettingsFactory::make([SettingKeys::PRIVACY_URL => '/privacy']),
            ['youtube' => ServiceFactory::youtubeConfig()],
        );
        $renderer = new ScriptRenderer();

        $head = $renderer->render([ConsentMode::script()], 'n0nce');
        $body = $renderer->render([
            Script::src('https://www.youtube.com/iframe_api', Category::Preferences),
            ...(new Banner($config))->scripts('/_consent/consent.js'),
        ], 'n0nce');

        static::assertSame(
            [
                'consent mode runs first' => 1,
                'the embed waits'         => 1,
                'the banner loads'        => 1,
                'the banner starts'       => 1,
            ],
            [
                'consent mode runs first' => preg_match('/^<script nonce="n0nce">window\.dataLayer/', $head),
                'the embed waits' => preg_match(
                    '#<script type="text/plain" data-category="preferences" src="https://www\.youtube\.com/iframe_api" nonce="n0nce"></script>#',
                    $body,
                ),
                'the banner loads' => preg_match(
                    '#<script src="/_consent/consent\.js" nonce="n0nce"></script>#',
                    $body,
                ),
                'the banner starts' => preg_match(
                    '#<script nonce="n0nce">window\.ContenirConsent && window\.ContenirConsent\.start\(#',
                    $body,
                ),
            ],
        );
    }

    #[Test]
    public function writesTheFootersPreferencesLinkInTheSitesWords(): void
    {
        $config = ConsentConfig::fromSettings([]);

        static::assertSame(
            '<a href="#" data-cc="show-preferencesModal">Cookie preferences</a>',
            (new PreferencesLink($config->texts->preferencesLink))->html(),
        );
    }
}
