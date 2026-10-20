<?php

declare(strict_types=1);

namespace Contenir\Consent\Tests\Unit;

use Contenir\Consent\Country;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class CountryTest extends TestCase
{
    /**
     * @return array<string, array{?string, ?string}>
     */
    public static function headers(): array
    {
        return [
            'a country'             => ['AU', 'AU'],
            'lower case and spaces' => [' gb ', 'GB'],
            'no header'             => [null, null],
            'an empty header'       => ['', null],
            "Cloudflare's unknown"  => ['XX', null],
            "Cloudflare's Tor"      => ['T1', null],
            'three letters'         => ['AUS', null],
            'one letter'            => ['A', null],
            'digits'                => ['12', null],
        ];
    }

    #[Test]
    #[DataProvider('headers')]
    public function readsTheCountryAHeaderNames(?string $header, ?string $code): void
    {
        static::assertSame($code, Country::fromHeader($header)?->code);
    }
}
