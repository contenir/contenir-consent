<?php

declare(strict_types=1);

namespace Contenir\Consent\Tests\Unit;

use Contenir\Consent\PreferencesLink;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class PreferencesLinkTest extends TestCase
{
    #[Test]
    public function dropsAttributesItWritesItselfEventHandlersAndBadNames(): void
    {
        static::assertSame(
            '<a href="#" data-cc="show-preferencesModal" id="prefs">Cookie preferences</a>',
            (new PreferencesLink())->html([
                'href'     => 'https://evil.example',
                'data-cc'  => 'accept-all',
                'onclick'  => 'steal()',
                'Bad Name' => 'x',
                "id\n"     => 'x',
                'id'       => 'prefs',
            ]),
        );
    }

    #[Test]
    public function escapesTheLabelAndAttributes(): void
    {
        static::assertSame(
            '<a href="#" data-cc="show-preferencesModal" class="footer &quot;link&quot;">Cookies &amp; you&apos;re</a>',
            (new PreferencesLink("Cookies & you're"))->html(['class' => 'footer "link"']),
        );
    }

    #[Test]
    public function writesALinkTheBannerOpensThePreferencesFrom(): void
    {
        static::assertSame(
            '<a href="#" data-cc="show-preferencesModal">Cookie preferences</a>',
            (new PreferencesLink())->html(),
        );
    }
}
