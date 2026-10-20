<?php

declare(strict_types=1);

namespace Contenir\Consent;

use Contenir\Consent\Exception\InvalidServiceException;
use Contenir\Consent\Internal\SettingsArray;

/**
 * A site's consent: who is asked first, how Google's tags meet consent, the
 * revision, what the banner says and the services it lists.
 *
 * @api
 */
final readonly class ConsentConfig
{
    /**
     * Cloudflare's country header, which by-country reads unless the site
     * names another.
     */
    public const string COUNTRY_HEADER = 'CF-IPCountry';

    /**
     * How long a choice is kept, whether it accepts or refuses, in days.
     */
    public const int DAYS = 182;

    /**
     * @param non-negative-int $revision      Raised to ask everyone again
     * @param string           $countryHeader The request header naming the visitor's country
     * @param ?string          $privacyUrl    The site's privacy policy
     *
     * @mago-expect lint:excessive-parameter-list One parameter per part of a site's consent.
     */
    public function __construct(
        public RegionalMode $regionalMode = RegionalMode::OptIn,
        public GoogleConsentMode $googleConsentMode = GoogleConsentMode::Basic,
        public int $revision = 0,
        public Texts $texts = new Texts(),
        public Services $services = new Services(),
        public string $countryHeader = self::COUNTRY_HEADER,
        public ?string $privacyUrl = null,
    ) {}

    /**
     * Reads the consent values from a site's settings, as nested arrays. A
     * value that is missing or can't be used falls back to its default, and
     * a blank title or description to the default text.
     *
     * @param array<array-key, mixed> $settings
     * @param array<array-key, mixed> $services The site's own services, by key (Service::fromArray())
     * @param Texts                   $texts    The texts to start from; the settings replace the title and description
     *
     * @throws InvalidServiceException When one of the site's services can't be used.
     */
    public static function fromSettings(array $settings, array $services = [], Texts $texts = new Texts()): self
    {
        $values      = new SettingsArray($settings);
        $title       = $values->text(SettingKeys::TITLE);
        $description = $values->text(SettingKeys::DESCRIPTION);
        $header      = $values->text(SettingKeys::COUNTRY_HEADER);
        $privacyUrl  = $values->text(SettingKeys::PRIVACY_URL);

        return new self(
            RegionalMode::tryFrom($values->text(SettingKeys::REGIONAL_MODE)) ?? RegionalMode::OptIn,
            GoogleConsentMode::tryFrom($values->text(SettingKeys::GOOGLE_CONSENT_MODE)) ?? GoogleConsentMode::Basic,
            $values->count(SettingKeys::REVISION, default: 0),
            new Texts(
                '' === $title ? $texts->title : $title,
                '' === $description ? $texts->description : $description,
                $texts->acceptAll,
                $texts->rejectAll,
                $texts->preferences,
                $texts->preferencesTitle,
                $texts->preferencesIntro,
                $texts->savePreferences,
                $texts->close,
                $texts->privacyPolicy,
                $texts->revised,
                $texts->service,
                $texts->provider,
                $texts->cookies,
                $texts->preferencesLink,
                $texts->language,
            ),
            Services::fromSettings($settings, $services),
            '' === $header ? self::COUNTRY_HEADER : $header,
            '' !== $privacyUrl && Service::link($privacyUrl) ? $privacyUrl : null,
        );
    }

    /**
     * Whether a visitor from $country must choose before anything that
     * needs consent runs.
     */
    public function asksFirst(?Country $country): bool
    {
        return $this->regionalMode->asksFirst($country);
    }
}
