<?php

declare(strict_types=1);

namespace Contenir\Consent;

/**
 * Where a site's settings keep each consent value, as dotted keys, under
 * extensions.consent, the Consent add-on's own namespace.
 *
 * @api
 */
final class SettingKeys
{
    /**
     * Who is asked first: opt-in, by-country or notice (RegionalMode).
     */
    public const string REGIONAL_MODE = 'extensions.consent.regional_mode';

    /**
     * The request header naming the visitor's country, for by-country.
     */
    public const string COUNTRY_HEADER = 'extensions.consent.country_header';

    /**
     * How Google's tags meet consent: basic or advanced (GoogleConsentMode).
     */
    public const string GOOGLE_CONSENT_MODE = 'extensions.consent.google_consent_mode';

    /**
     * The consent revision; raising it asks everyone again.
     */
    public const string REVISION = 'extensions.consent.revision';

    public const string TITLE = 'extensions.consent.title';

    public const string DESCRIPTION = 'extensions.consent.description';

    /**
     * The site's privacy policy, linked from the banner and preferences.
     */
    public const string PRIVACY_URL = 'extensions.consent.privacy_url';
}
