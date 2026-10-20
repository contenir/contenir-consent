<?php

declare(strict_types=1);

namespace Contenir\Consent\Tests\Unit;

use Contenir\Consent\Country;
use Contenir\Consent\RegionalMode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function count;

#[Group('unit')]
final class RegionalModeTest extends TestCase
{
    /**
     * @return array<string, array{RegionalMode, ?string, bool}>
     */
    public static function visitors(): array
    {
        return [
            'opt-in asks an Australian'          => [RegionalMode::OptIn, 'AU', true],
            'opt-in asks an unknown visitor'     => [RegionalMode::OptIn, null, true],
            'notice tells a German'              => [RegionalMode::Notice, 'DE', false],
            'notice tells an unknown visitor'    => [RegionalMode::Notice, null, false],
            'by country asks a German'           => [RegionalMode::ByCountry, 'DE', true],
            'by country asks a Briton'           => [RegionalMode::ByCountry, 'GB', true],
            'by country asks a Swiss visitor'    => [RegionalMode::ByCountry, 'CH', true],
            'by country asks a Norwegian'        => [RegionalMode::ByCountry, 'NO', true],
            'by country tells an Australian'     => [RegionalMode::ByCountry, 'AU', false],
            'by country tells a New Zealander'   => [RegionalMode::ByCountry, 'NZ', false],
            'by country tells an American'       => [RegionalMode::ByCountry, 'US', false],
            'by country asks an unknown visitor' => [RegionalMode::ByCountry, null, true],
        ];
    }

    #[Test]
    public function asksTheTwentySevenEuCountriesTheRestOfTheEeaTheUkAndSwitzerland(): void
    {
        static::assertSame(32, count(RegionalMode::OPT_IN_COUNTRIES));
    }

    #[Test]
    #[DataProvider('visitors')]
    public function decidesWhoIsAskedFirst(RegionalMode $mode, ?string $country, bool $asksFirst): void
    {
        static::assertSame($asksFirst, $mode->asksFirst(Country::fromHeader($country)));
    }

    #[Test]
    public function namesEachModeForTheSettings(): void
    {
        static::assertSame(
            [
                'Ask everyone first',
                'Ask visitors from the EEA, UK and Switzerland; tell everyone else',
                'Tell visitors, without asking first',
            ],
            [RegionalMode::OptIn->label(), RegionalMode::ByCountry->label(), RegionalMode::Notice->label()],
        );
    }
}
