<?php

declare(strict_types=1);

namespace Contenir\Consent\Tests\Unit;

use Contenir\Consent\Category;
use Contenir\Consent\ConsentMode;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ConsentModeTest extends TestCase
{
    #[Test]
    public function alwaysRuns(): void
    {
        static::assertSame(Category::Necessary, ConsentMode::script()->category);
    }

    #[Test]
    public function deniesEverySignalBeforeAnyTagAndUpdatesThemFromEachChoice(): void
    {
        static::assertSame(
            <<<'JS'
                window.dataLayer = window.dataLayer || [];
                window.gtag = window.gtag || function gtag() { window.dataLayer.push(arguments); };
                gtag('consent', 'default', {"ad_storage":"denied","ad_user_data":"denied","ad_personalization":"denied","analytics_storage":"denied","wait_for_update":500});
                gtag('set', 'ads_data_redaction', true);
                window.addEventListener("contenir:consent", function (event) {
                    var detail = event.detail || {};
                    var analytics = detail.analytics === true;
                    var ads = detail.ads === true;
                    window["contenirConsent"] = {analytics: analytics, ads: ads};
                    gtag('consent', 'update', {ad_storage: ads ? 'granted' : 'denied', ad_user_data: ads ? 'granted' : 'denied', ad_personalization: ads ? 'granted' : 'denied', analytics_storage: analytics ? 'granted' : 'denied'});
                });
                JS,
            ConsentMode::script()->inline,
        );
    }
}
