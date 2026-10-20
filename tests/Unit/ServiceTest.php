<?php

declare(strict_types=1);

namespace Contenir\Consent\Tests\Unit;

use Contenir\Consent\Category;
use Contenir\Consent\Exception\InvalidServiceException;
use Contenir\Consent\Service;
use Contenir\Consent\Tests\TestAsset\Factory\ServiceFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ServiceTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function badConfigs(): array
    {
        return [
            'no category'       => [
                ['category' => ''],
                'The service "youtube" needs a category: necessary, analytics, marketing or preferences',
            ],
            'unknown category'  => [
                ['category' => 'social'],
                'The service "youtube" needs a category: necessary, analytics, marketing or preferences',
            ],
            'category not text' => [
                ['category' => 3],
                'The service "youtube" needs a category: necessary, analytics, marketing or preferences',
            ],
            'no name'           => [['name' => '  '], 'The service "youtube" needs a name'],
            'a script link'     => [
                ['privacy_url' => 'javascript:alert(1)'],
                'The privacy link for "youtube" must be an http(s) address or a path starting with /',
            ],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function badKeys(): array
    {
        return [
            'empty'           => [''],
            'upper case'      => ['YouTube'],
            'a leading digit' => ['1video'],
            'a space'         => ['you tube'],
            'a new line'      => ["youtube\n"],
        ];
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function links(): array
    {
        return [
            'https'             => ['https://example.com/privacy', true],
            'http'              => ['http://example.com/privacy', true],
            'a path'            => ['/privacy', true],
            'protocol-relative' => ['//example.com/privacy', false],
            'javascript'        => ['javascript:alert(1)', false],
            'relative'          => ['privacy', false],
            'mailto'            => ['mailto:privacy@example.com', false],
        ];
    }

    #[Test]
    public function acceptsAKeyWithDigitsAndDashes(): void
    {
        static::assertSame('video-2', ServiceFactory::make(key: 'video-2')->key);
    }

    #[Test]
    #[DataProvider('links')]
    public function linksOnlyToWebAddressesAndPathsOnTheSite(string $url, bool $safe): void
    {
        static::assertSame($safe, Service::link($url));
    }

    #[Test]
    public function readsAServiceFromASitesConfig(): void
    {
        static::assertEquals(
            new Service(
                'youtube',
                'YouTube',
                'Google',
                Category::Preferences,
                ['YSC', 'VISITOR_INFO1_LIVE'],
                'https://policies.google.com/privacy',
            ),
            Service::fromArray('youtube', ServiceFactory::youtubeConfig()),
        );
    }

    #[Test]
    public function readsAServiceWithoutCookiesOrAPrivacyLink(): void
    {
        $service = Service::fromArray('youtube', ServiceFactory::youtubeConfig([
            'provider'    => 7,
            'cookies'     => 'YSC',
            'privacy_url' => '',
        ]));

        static::assertSame(['', [], null], [$service->provider, $service->cookies, $service->privacyUrl]);
    }

    #[Test]
    public function refusesABlankName(): void
    {
        $this->expectException(InvalidServiceException::class);
        $this->expectExceptionMessage('The service "video" needs a name');

        ServiceFactory::make(name: '   ');
    }

    #[Test]
    #[DataProvider('badConfigs')]
    public function refusesAConfigItCannotList(array $values, string $message): void
    {
        $this->expectException(InvalidServiceException::class);
        $this->expectExceptionMessage($message);

        Service::fromArray('youtube', ServiceFactory::youtubeConfig($values));
    }

    #[Test]
    #[DataProvider('badKeys')]
    public function refusesAKeyThatIsNotLowerCaseLettersDigitsAndDashes(string $key): void
    {
        $this->expectException(InvalidServiceException::class);
        $this->expectExceptionMessage(
            "A service key is lower case letters, digits and dashes, starting with a letter; \"{$key}\" is not",
        );

        ServiceFactory::make(key: $key);
    }

    #[Test]
    public function trimsValuesAndDropsCookiesThatAreNotNames(): void
    {
        $service = Service::fromArray('youtube', ServiceFactory::youtubeConfig([
            'name'        => ' YouTube ',
            'provider'    => ' Google ',
            'cookies'     => [' YSC ', '', 4, 'PREF'],
            'privacy_url' => ' /privacy ',
        ]));

        static::assertSame(
            ['YouTube', 'Google', ['YSC', 'PREF'], '/privacy'],
            [$service->name, $service->provider, $service->cookies, $service->privacyUrl],
        );
    }
}
