<?php

declare(strict_types=1);

namespace Contenir\Consent\Tests\Unit;

use Contenir\Consent\Category;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class CategoryTest extends TestCase
{
    /**
     * @return array<string, array{Category, string, string}>
     */
    public static function categories(): array
    {
        return [
            'necessary'   => [
                Category::Necessary,
                'Strictly necessary',
                'Makes the site work and remembers your cookie choices. These are always on.',
            ],
            'analytics'   => [
                Category::Analytics,
                'Analytics',
                'Counts visits and shows how the site is used, so we can improve it.',
            ],
            'marketing'   => [
                Category::Marketing,
                'Marketing',
                'Measures advertising and shows ads that suit you, on this site and others.',
            ],
            'preferences' => [
                Category::Preferences,
                'Preferences',
                'Remembers your settings and shows content from other sites, such as videos and maps.',
            ],
        ];
    }

    #[Test]
    #[DataProvider('categories')]
    public function describesEachCategoryForThePreferences(
        Category $category,
        string $label,
        string $description,
    ): void {
        static::assertSame([$label, $description], [$category->label(), $category->description()]);
    }

    #[Test]
    public function listsNecessaryFirstThenAnalyticsMarketingAndPreferences(): void
    {
        static::assertSame(
            [Category::Necessary, Category::Analytics, Category::Marketing, Category::Preferences],
            Category::ordered(),
        );
    }

    #[Test]
    public function matchesTheExtensionApisCategoryNames(): void
    {
        static::assertSame(
            ['analytics', 'marketing', 'necessary', 'preferences'],
            [
                Category::Analytics->value,
                Category::Marketing->value,
                Category::Necessary->value,
                Category::Preferences->value,
            ],
        );
    }
}
