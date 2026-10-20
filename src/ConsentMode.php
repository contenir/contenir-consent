<?php

declare(strict_types=1);

namespace Contenir\Consent;

use Contenir\Consent\Script\Script;

use function strtr;

/**
 * Google Consent Mode v2 for a site without the Tracking add-on: every
 * signal denied before any tag loads, and an update for each
 * contenir:consent choice. The last choice is kept in
 * window.contenirConsent, as contenir/tracking's bridge keeps it.
 *
 * A site with the Tracking add-on doesn't need this: Tracking writes the
 * same defaults first and maps each choice itself, and a second set of
 * defaults would only repeat them.
 *
 * @api
 */
final class ConsentMode
{
    /**
     * The browser event a banner fires with the visitor's choice,
     * {analytics, ads}.
     */
    public const string EVENT = 'contenir:consent';

    /**
     * The window property holding the last choice.
     */
    public const string STATE = 'contenirConsent';

    /**
     * How long Google's tags wait for the stored choice, in milliseconds.
     */
    public const int WAIT_FOR_UPDATE = 500;

    /**
     * The defaults and the bridge, as the first script in the page's head.
     */
    public static function script(): Script
    {
        return Script::inline(
            strtr(
                <<<'JS'
                    window.dataLayer = window.dataLayer || [];
                    window.gtag = window.gtag || function gtag() { window.dataLayer.push(arguments); };
                    gtag('consent', 'default', {"ad_storage":"denied","ad_user_data":"denied","ad_personalization":"denied","analytics_storage":"denied","wait_for_update":{wait}});
                    gtag('set', 'ads_data_redaction', true);
                    window.addEventListener("{event}", function (event) {
                        var detail = event.detail || {};
                        var analytics = detail.analytics === true;
                        var ads = detail.ads === true;
                        window["{state}"] = {analytics: analytics, ads: ads};
                        gtag('consent', 'update', {ad_storage: ads ? 'granted' : 'denied', ad_user_data: ads ? 'granted' : 'denied', ad_personalization: ads ? 'granted' : 'denied', analytics_storage: analytics ? 'granted' : 'denied'});
                    });
                    JS,
                ['{event}' => self::EVENT, '{state}' => self::STATE, '{wait}' => (string) self::WAIT_FOR_UPDATE],
            ),
            Category::Necessary,
        );
    }
}
