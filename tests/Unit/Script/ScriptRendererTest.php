<?php

declare(strict_types=1);

namespace Contenir\Consent\Tests\Unit\Script;

use Contenir\Consent\Category;
use Contenir\Consent\Script\Script;
use Contenir\Consent\Script\ScriptRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ScriptRendererTest extends TestCase
{
    /**
     * @return array<string, array{Category, string}>
     */
    public static function gated(): array
    {
        return [
            'analytics'   => [Category::Analytics, '<script type="text/plain" data-category="analytics">a();</script>'],
            'marketing'   => [Category::Marketing, '<script type="text/plain" data-category="marketing">a();</script>'],
            'preferences' => [
                Category::Preferences,
                '<script type="text/plain" data-category="preferences">a();</script>',
            ],
            'necessary'   => [Category::Necessary, '<script>a();</script>'],
        ];
    }

    #[Test]
    public function dropsAttributesItWritesItselfEventHandlersAndBadNames(): void
    {
        $html = (new ScriptRenderer())->script(Script::inline('a();', Category::Necessary, [
            'type'          => 'module',
            'src'           => 'https://evil.example/x.js',
            'nonce'         => 'stolen',
            'data-category' => 'necessary',
            'onload'        => 'steal()',
            'Bad Name'      => 'x',
            "id\n"          => 'x',
            'data-ok'       => 'yes',
        ]));

        static::assertSame('<script data-ok="yes">a();</script>', $html);
    }

    #[Test]
    #[DataProvider('gated')]
    public function writesEveryScriptButNecessaryOnesInertWhileConsentGatesThem(Category $category, string $html): void
    {
        static::assertSame($html, (new ScriptRenderer())->script(Script::inline('a();', $category)));
    }

    #[Test]
    public function writesNoNonceWhenThePageHasNone(): void
    {
        $html = (new ScriptRenderer())->script(Script::inline('a();', Category::Necessary), '');

        static::assertSame('<script>a();</script>', $html);
    }

    #[Test]
    public function writesScriptsOneALine(): void
    {
        $html = (new ScriptRenderer())->render(
            [Script::inline('a();', Category::Necessary), Script::src('/b.js', Category::Marketing)],
            'n',
        );

        static::assertSame(
            '<script nonce="n">a();</script>'
                . "\n"
                . '<script type="text/plain" data-category="marketing" src="/b.js" nonce="n"></script>',
            $html,
        );
    }

    #[Test]
    public function writesScriptsToRunWhenNothingGatesThem(): void
    {
        $html = (new ScriptRenderer(gate: false))->script(Script::inline('a();', Category::Marketing));

        static::assertSame('<script>a();</script>', $html);
    }

    #[Test]
    public function writesTheSrcNonceAndAttributesEscaped(): void
    {
        $html = (new ScriptRenderer())->script(
            Script::src('https://example.com/a.js?x=1&y="2"', Category::Analytics, [
                'async' => true,
                'defer' => false,
                'id'    => 'a"b\'c',
            ]),
            'n0n"ce',
        );

        static::assertSame(
            '<script type="text/plain" data-category="analytics" src="https://example.com/a.js?x=1&amp;y=&quot;2&quot;" '
                . 'nonce="n0n&quot;ce" async id="a&quot;b&apos;c"></script>',
            $html,
        );
    }
}
