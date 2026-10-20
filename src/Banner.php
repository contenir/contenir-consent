<?php

declare(strict_types=1);

namespace Contenir\Consent;

use Contenir\Consent\Internal\Js;
use Contenir\Consent\Script\Script;

use function array_filter;
use function array_map;
use function htmlspecialchars;
use function implode;
use function sprintf;

use const ENT_HTML5;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * The banner and preferences: vanilla-cookieconsent v3's configuration for
 * one visitor, and the scripts that load and start it.
 *
 * The banner sits in the bottom right as cookieconsent's wide box, with
 * Accept all, Reject all and Manage preferences equally prominent. The
 * preferences list each category with its services. A choice is kept for
 * 182 days, whether it accepts or refuses, and raising the revision asks
 * everyone again.
 *
 * Starting it (Assets' script, window.ContenirConsent.start()) fires
 * contenir:consent with the stored choice before any gated script runs,
 * switches on the scripts each choice allows, fires the event again for
 * every new choice, and records Global Privacy Control with it.
 *
 * @api
 */
final readonly class Banner
{
    /**
     * The data-cc value cookieconsent opens the preferences for, on any
     * element in the page when it starts.
     */
    public const string SHOW_PREFERENCES = 'show-preferencesModal';

    public function __construct(
        private ConsentConfig $config,
    ) {}

    /**
     * cookieconsent's configuration for a visitor from $country, ready to
     * write as JSON. Everything in it that cookieconsent writes as HTML is
     * escaped here.
     *
     * @return array<string, mixed>
     */
    public function config(?Country $country = null): array
    {
        $asksFirst = $this->config->asksFirst($country);
        $texts     = $this->config->texts;

        return [
            'mode'             => $asksFirst ? 'opt-in' : 'opt-out',
            'revision'         => $this->config->revision,
            'autoShow'         => true,
            'manageScriptTags' => false,
            'autoClearCookies' => true,
            'hideFromBots'     => true,
            'cookie'           => [
                'name'             => Services::CONSENT_COOKIE,
                'expiresAfterDays' => ConsentConfig::DAYS,
                'sameSite'         => 'Lax',
            ],
            'guiOptions'       => [
                'consentModal'     => [
                    'layout'             => 'box wide',
                    'position'           => 'bottom right',
                    'equalWeightButtons' => true,
                    'flipButtons'        => false,
                ],
                'preferencesModal' => [
                    'layout'             => 'box',
                    'equalWeightButtons' => true,
                    'flipButtons'        => false,
                ],
            ],
            'categories'       => $this->categories($asksFirst),
            'language'         => [
                'default'      => $texts->language,
                'translations' => [
                    $texts->language => [
                        'consentModal'     => array_filter([
                            'title'              => $this->escape($texts->title),
                            'description'        => "{$this->escape($texts->description)}{{revisionMessage}}",
                            'revisionMessage'    => " {$this->escape($texts->revised)}",
                            'acceptAllBtn'       => $this->escape($texts->acceptAll),
                            'acceptNecessaryBtn' => $this->escape($texts->rejectAll),
                            'showPreferencesBtn' => $this->escape($texts->preferences),
                            'footer'             => $this->privacyLink(),
                        ]),
                        'preferencesModal' => [
                            'title'              => $this->escape($texts->preferencesTitle),
                            'acceptAllBtn'       => $this->escape($texts->acceptAll),
                            'acceptNecessaryBtn' => $this->escape($texts->rejectAll),
                            'savePreferencesBtn' => $this->escape($texts->savePreferences),
                            'closeIconLabel'     => $this->escape($texts->close),
                            'sections'           => $this->sections(),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * The scripts for the end of the body: cookieconsent with Consent's own
     * code (Assets' script) loaded from $assetUrl, then the call that starts
     * it for a visitor from $country. Both are necessary, so they always run.
     *
     * @return list<Script>
     */
    public function scripts(string $assetUrl, ?Country $country = null): array
    {
        return [
            Script::src($assetUrl, Category::Necessary),
            Script::inline(
                sprintf(
                    'window.ContenirConsent && window.ContenirConsent.start(%s);',
                    Js::value(['config' => $this->config($country)]),
                ),
                Category::Necessary,
            ),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function categories(bool $asksFirst): array
    {
        $categories = [];
        foreach (Category::ordered() as $category) {
            $necessary = Category::Necessary === $category;
            $cookies   = $necessary ? [] : $this->cookies($category);

            $categories[$category->value] = array_filter(
                [
                    'enabled'   => $necessary || ! $asksFirst,
                    'readOnly'  => $necessary,
                    'autoClear' => [] === $cookies ? null : ['cookies' => $cookies],
                ],
                static fn(mixed $value): bool => null !== $value,
            );
        }

        return $categories;
    }

    /**
     * The cookies the services under $category set, for cookieconsent to
     * clear when the visitor refuses it.
     *
     * @return list<array{name: string}>
     */
    private function cookies(Category $category): array
    {
        $cookies = [];
        foreach ($this->config->services->in($category) as $service) {
            foreach ($service->cookies as $cookie) {
                $cookies[] = ['name' => $cookie];
            }
        }

        return $cookies;
    }

    private function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, encoding: 'UTF-8');
    }

    private function privacyLink(): string
    {
        return null === $this->config->privacyUrl
            ? ''
            : sprintf(
                '<a href="%s">%s</a>',
                $this->escape($this->config->privacyUrl),
                $this->escape($this->config->texts->privacyPolicy),
            );
    }

    /**
     * The preferences' sections: an introduction, then each category with a
     * table of its services.
     *
     * @return list<array<string, mixed>>
     */
    private function sections(): array
    {
        $texts = $this->config->texts;
        $link  = $this->privacyLink();
        $intro = [
            'description' => $this->escape($texts->preferencesIntro) . ('' === $link ? '' : " {$link}"),
        ];

        return [
            $intro,
            ...array_map(
                /** @return array<string, mixed> */
                fn(Category $category): array => array_filter([
                    'title'          => $this->escape($category->label()),
                    'description'    => $this->escape($category->description()),
                    'linkedCategory' => $category->value,
                    'cookieTable'    => $this->table($category),
                ]),
                Category::ordered(),
            ),
        ];
    }

    /**
     * The table of services under $category, or null when it has none.
     *
     * @return ?array{headers: array<string, string>, body: list<array<string, string>>}
     */
    private function table(Category $category): ?array
    {
        $services = $this->config->services->in($category);
        if ([] === $services) {
            return null;
        }

        $texts = $this->config->texts;

        return [
            'headers' => [
                'name'     => $this->escape($texts->service),
                'provider' => $this->escape($texts->provider),
                'cookies'  => $this->escape($texts->cookies),
            ],
            'body'    => array_map(
                /** @return array<string, string> */
                fn(Service $service): array => [
                    'name'     => null === $service->privacyUrl
                        ? $this->escape($service->name)
                        : sprintf(
                            '<a href="%s">%s</a>',
                            $this->escape($service->privacyUrl),
                            $this->escape($service->name),
                        ),
                    'provider' => $this->escape($service->provider),
                    'cookies'  => $this->escape(implode(', ', $service->cookies)),
                ],
                $services,
            ),
        ];
    }
}
