<?php

declare(strict_types=1);

namespace Contenir\Consent\Tests\Unit;

use Contenir\Consent\Banner;
use Contenir\Consent\Category;
use Contenir\Consent\ConsentConfig;
use Contenir\Consent\Country;
use Contenir\Consent\Internal\Js;
use Contenir\Consent\RegionalMode;
use Contenir\Consent\Services;
use Contenir\Consent\Tests\TestAsset\Factory\ServiceFactory;
use Contenir\Consent\Texts;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_key_exists;

#[Group('unit')]
final class BannerTest extends TestCase
{
    #[Test]
    public function asksAVisitorFromTheEeaFirst(): void
    {
        $config = (new Banner(new ConsentConfig(regionalMode: RegionalMode::ByCountry)))->config(
            Country::fromHeader('IE'),
        );

        static::assertSame('opt-in', $config['mode']);
    }

    #[Test]
    public function configuresCookieconsentForAVisitorWhoIsAskedFirst(): void
    {
        $config = $this->askedFirst();
        unset($config['language']);

        static::assertSame(
            [
                'mode'             => 'opt-in',
                'revision'         => 2,
                'autoShow'         => true,
                'manageScriptTags' => false,
                'autoClearCookies' => true,
                'hideFromBots'     => true,
                'cookie'           => ['name' => 'cc_cookie', 'expiresAfterDays' => 182, 'sameSite' => 'Lax'],
                'guiOptions'       => [
                    'consentModal'     => [
                        'layout'             => 'box wide',
                        'position'           => 'bottom right',
                        'equalWeightButtons' => true,
                        'flipButtons'        => false,
                    ],
                    'preferencesModal' => ['layout' => 'box', 'equalWeightButtons' => true, 'flipButtons' => false],
                ],
                'categories'       => [
                    'necessary'   => ['enabled' => true, 'readOnly' => true],
                    'analytics'   => [
                        'enabled'   => false,
                        'readOnly'  => false,
                        'autoClear' => ['cookies' => [['name' => '_st'], ['name' => '_st_*']]],
                    ],
                    'marketing'   => ['enabled' => false, 'readOnly' => false],
                    'preferences' => ['enabled' => false, 'readOnly' => false],
                ],
            ],
            $config,
        );
    }

    #[Test]
    public function escapesEverythingCookieconsentWritesAsHtml(): void
    {
        $banner = new Banner(new ConsentConfig(
            texts: new Texts(
                title: '<b>Cookies</b>',
                description: 'Us & them',
                acceptAll: '"Yes"',
                rejectAll: "'No'",
                preferences: '<i>',
                preferencesTitle: '<h1>',
                preferencesIntro: 'A < B',
                savePreferences: 'Save >',
                close: 'X & Y',
                privacyPolicy: 'Privacy & you',
                revised: 'New <stuff>',
                service: 'S&',
                provider: 'P&',
                cookies: 'C&',
                language: 'en-AU',
            ),
            services: new Services([ServiceFactory::make(
                'video',
                'Vid & co',
                Category::Marketing,
                ['a&b'],
                '/p?a=1&b=2',
            )]),
            privacyUrl: '/privacy?a="1"',
        ));
        $text = $banner->config()['language']['translations']['en-AU'];

        static::assertSame(
            [
                '&lt;b&gt;Cookies&lt;/b&gt;',
                'Us &amp; them{{revisionMessage}}',
                ' New &lt;stuff&gt;',
                '&quot;Yes&quot;',
                '&apos;No&apos;',
                '&lt;i&gt;',
                '<a href="/privacy?a=&quot;1&quot;">Privacy &amp; you</a>',
                '&lt;h1&gt;',
                '&quot;Yes&quot;',
                '&apos;No&apos;',
                'Save &gt;',
                'X &amp; Y',
                'A &lt; B <a href="/privacy?a=&quot;1&quot;">Privacy &amp; you</a>',
                ['name' => 'S&amp;', 'provider' => 'P&amp;', 'cookies' => 'C&amp;'],
                [
                    'name'     => '<a href="/p?a=1&amp;b=2">Vid &amp; co</a>',
                    'provider' => 'Video Ltd',
                    'cookies'  => 'a&amp;b',
                ],
            ],
            [
                $text['consentModal']['title'],
                $text['consentModal']['description'],
                $text['consentModal']['revisionMessage'],
                $text['consentModal']['acceptAllBtn'],
                $text['consentModal']['acceptNecessaryBtn'],
                $text['consentModal']['showPreferencesBtn'],
                $text['consentModal']['footer'],
                $text['preferencesModal']['title'],
                $text['preferencesModal']['acceptAllBtn'],
                $text['preferencesModal']['acceptNecessaryBtn'],
                $text['preferencesModal']['savePreferencesBtn'],
                $text['preferencesModal']['closeIconLabel'],
                $text['preferencesModal']['sections'][0]['description'],
                $text['preferencesModal']['sections'][3]['cookieTable']['headers'],
                $text['preferencesModal']['sections'][3]['cookieTable']['body'][0],
            ],
        );
    }

    #[Test]
    public function linksToNoPrivacyPolicyWhenTheSiteHasNone(): void
    {
        $text = (new Banner(new ConsentConfig()))->config()['language']['translations']['en'];

        static::assertSame(
            [
                false,
                'Choose which cookies this site may use. Strictly necessary cookies are always on, because the site '
                    . 'needs them.',
            ],
            [
                array_key_exists('footer', $text['consentModal']),
                $text['preferencesModal']['sections'][0]['description'],
            ],
        );
    }

    #[Test]
    public function listsEachCategoryWithItsServicesInThePreferences(): void
    {
        $headers = ['name' => 'Service', 'provider' => 'Provider', 'cookies' => 'Cookies'];

        static::assertSame(
            [
                [
                    'description' =>
                        'Choose which cookies this site may use. Strictly necessary cookies are always '
                            . 'on, because the site needs them. <a href="/privacy">Privacy policy</a>',
                ],
                [
                    'title'          => 'Strictly necessary',
                    'description'    => 'Makes the site work and remembers your cookie choices. These are always on.',
                    'linkedCategory' => 'necessary',
                    'cookieTable'    => [
                        'headers' => $headers,
                        'body'    => [[
                            'name'     => 'Cookie choices',
                            'provider' => 'Video Ltd',
                            'cookies'  => 'cc_cookie',
                        ]],
                    ],
                ],
                [
                    'title'          => 'Analytics',
                    'description'    => 'Counts visits and shows how the site is used, so we can improve it.',
                    'linkedCategory' => 'analytics',
                    'cookieTable'    => [
                        'headers' => $headers,
                        'body'    => [
                            [
                                'name'     => '<a href="https://stats.example/p">Stats</a>',
                                'provider' => 'Video Ltd',
                                'cookies'  => '_st, _st_*',
                            ],
                        ],
                    ],
                ],
                [
                    'title'          => 'Marketing',
                    'description'    => 'Measures advertising and shows ads that suit you, on this site and others.',
                    'linkedCategory' => 'marketing',
                ],
                [
                    'title'          => 'Preferences',
                    'description'    => 'Remembers your settings and shows content from other sites, such as videos '
                        . 'and maps.',
                    'linkedCategory' => 'preferences',
                ],
            ],
            $this->askedFirst()['language']['translations']['en']['preferencesModal']['sections'],
        );
    }

    #[Test]
    public function loadsCookieconsentThenStartsItForTheVisitor(): void
    {
        $banner  = new Banner(new ConsentConfig(regionalMode: RegionalMode::Notice));
        $scripts = $banner->scripts('/_consent/consent-abc.js');

        static::assertSame(
            [
                [Category::Necessary, '/_consent/consent-abc.js', null],
                [
                    Category::Necessary,
                    null,
                    'window.ContenirConsent && window.ContenirConsent.start({"config":'
                        . Js::value($banner->config())
                        . '});',
                ],
            ],
            [
                [$scripts[0]->category, $scripts[0]->src, $scripts[0]->inline],
                [$scripts[1]->category, $scripts[1]->src, $scripts[1]->inline],
            ],
        );
    }

    #[Test]
    public function runsEveryCategoryAtOnceForAVisitorWhoIsOnlyTold(): void
    {
        $config = (new Banner(new ConsentConfig(regionalMode: RegionalMode::ByCountry)))->config(
            Country::fromHeader('AU'),
        );

        static::assertSame(
            [
                'opt-out',
                ['enabled' => true, 'readOnly' => true],
                ['enabled' => true, 'readOnly' => false],
                ['enabled' => true, 'readOnly' => false],
                ['enabled' => true, 'readOnly' => false],
            ],
            [
                $config['mode'],
                $config['categories']['necessary'],
                $config['categories']['analytics'],
                $config['categories']['marketing'],
                $config['categories']['preferences'],
            ],
        );
    }

    #[Test]
    public function startsItForTheVisitorsCountry(): void
    {
        $banner  = new Banner(new ConsentConfig(regionalMode: RegionalMode::ByCountry));
        $scripts = $banner->scripts('/consent.js', Country::fromHeader('NZ'));

        static::assertStringContainsString('"mode":"opt-out"', (string) $scripts[1]->inline);
    }

    #[Test]
    public function writesTheBannerAndPreferencesTextsInTheirLanguage(): void
    {
        $language = $this->askedFirst()['language'];
        unset($language['translations']['en']['preferencesModal']['sections']);

        static::assertSame(
            [
                'default'      => 'en',
                'translations' => [
                    'en' => [
                        'consentModal'     => [
                            'title'              => 'Cookies',
                            'description'        => 'We use some.{{revisionMessage}}',
                            'revisionMessage'    => ' We have changed how we use cookies since you last chose.',
                            'acceptAllBtn'       => 'Accept all',
                            'acceptNecessaryBtn' => 'Reject all',
                            'showPreferencesBtn' => 'Manage preferences',
                            'footer'             => '<a href="/privacy">Privacy policy</a>',
                        ],
                        'preferencesModal' => [
                            'title'              => 'Cookie preferences',
                            'acceptAllBtn'       => 'Accept all',
                            'acceptNecessaryBtn' => 'Reject all',
                            'savePreferencesBtn' => 'Save preferences',
                            'closeIconLabel'     => 'Close',
                        ],
                    ],
                ],
            ],
            $language,
        );
    }

    /**
     * The configuration for a visitor asked first, on a site with a
     * privacy policy, revision 2, and a service in two categories.
     *
     * @return array<string, mixed>
     */
    private function askedFirst(): array
    {
        $banner = new Banner(new ConsentConfig(
            revision: 2,
            texts: new Texts(
                title: 'Cookies',
                description: 'We use some.',
            ),
            services: new Services([
                ServiceFactory::make('consent', 'Cookie choices', Category::Necessary, ['cc_cookie']),
                ServiceFactory::make(
                    'stats',
                    'Stats',
                    Category::Analytics,
                    ['_st', '_st_*'],
                    'https://stats.example/p',
                ),
            ]),
            privacyUrl: '/privacy',
        ));

        return $banner->config();
    }
}
