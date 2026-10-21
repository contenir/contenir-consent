<?php

declare(strict_types=1);

namespace Contenir\Consent\Tests\Integration;

use Contenir\Consent\Assets;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function strpos;

/**
 * The banner's browser files, read from the package's assets/ folder.
 */
#[Group('integration')]
final class AssetsTest extends TestCase
{
    #[Test]
    public function namesTheFilesAfterAHashOfWhatTheyHoldNow(): void
    {
        static::assertSame(Assets::hash(), Assets::HASH, 'Set Assets::HASH to Assets::hash() after changing assets/');
    }

    #[Test]
    public function putsConsentsChangesAfterCookieconsentsStylesheet(): void
    {
        $stylesheet = Assets::stylesheet();

        static::assertGreaterThan(
            (int) strpos($stylesheet, needle: '#cc-main .cm__btn--secondary{'),
            (int) strpos($stylesheet, needle: '#cc-main .cm__btn--secondary:not(.cm__btn--close) {'),
        );
    }

    #[Test]
    public function putsConsentsScriptAfterTheCookieconsentRelease(): void
    {
        $script = Assets::script();

        static::assertSame(
            [0, true],
            [
                strpos($script, "/*!\n* CookieConsent " . Assets::COOKIECONSENT),
                strpos($script, needle: "/*!\n * contenir/contenir-consent") > strpos(
                    $script,
                    needle: '.CookieConsent={});',
                ),
            ],
        );
    }

    #[Test]
    public function servesTheFilesUnderTheirHashedNames(): void
    {
        static::assertSame(
            ['consent-' . Assets::HASH . '.js', 'consent-' . Assets::HASH . '.css'],
            [Assets::scriptName(), Assets::stylesheetName()],
        );
    }
}
