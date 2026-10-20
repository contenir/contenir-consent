<?php

declare(strict_types=1);

namespace Contenir\Consent;

/**
 * What the banner and preferences say. The title and description are
 * settings; the rest are the defaults below, which a site adapter may
 * replace.
 *
 * @api
 */
final readonly class Texts
{
    public const string TITLE = 'We use cookies';

    public const string DESCRIPTION =
        'Some cookies make this site work. With your permission, we would also like to '
            . 'use cookies to understand how the site is used and to measure our advertising. You can change your '
            . 'choice at any time.';

    /**
     * @param string $title           The banner's heading
     * @param string $description     The banner's text, as plain text
     * @param string $revised         Added to the description when the site asks everyone again
     * @param string $preferencesLink The words on the link that opens the preferences
     * @param string $language        The language the texts are in, as a BCP 47 tag such as en or en-AU
     *
     * @mago-expect lint:excessive-parameter-list One named parameter per text, so a site replaces only those it names.
     */
    public function __construct(
        public string $title = self::TITLE,
        public string $description = self::DESCRIPTION,
        public string $acceptAll = 'Accept all',
        public string $rejectAll = 'Reject all',
        public string $preferences = 'Manage preferences',
        public string $preferencesTitle = 'Cookie preferences',
        public string $preferencesIntro = 'Choose which cookies this site may use. Strictly necessary cookies are '
            . 'always on, because the site needs them.',
        public string $savePreferences = 'Save preferences',
        public string $close = 'Close',
        public string $privacyPolicy = 'Privacy policy',
        public string $revised = 'We have changed how we use cookies since you last chose.',
        public string $service = 'Service',
        public string $provider = 'Provider',
        public string $cookies = 'Cookies',
        public string $preferencesLink = 'Cookie preferences',
        public string $language = 'en',
    ) {}
}
