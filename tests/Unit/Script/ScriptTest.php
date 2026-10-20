<?php

declare(strict_types=1);

namespace Contenir\Consent\Tests\Unit\Script;

use Contenir\Consent\Category;
use Contenir\Consent\Exception\InvalidScriptException;
use Contenir\Consent\Script\Script;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ScriptTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function closingCode(): array
    {
        return [
            'lower case' => ['document.write("</script>")'],
            'upper case' => ['document.write("</SCRIPT>")'],
        ];
    }

    #[Test]
    public function holdsAnInlineScript(): void
    {
        $script = Script::inline('init();', Category::Marketing, ['id' => 'chat']);

        static::assertSame(
            [Category::Marketing, null, 'init();', ['id' => 'chat']],
            [$script->category, $script->src, $script->inline, $script->attributes],
        );
    }

    #[Test]
    public function holdsAScriptLoadedFromAUrl(): void
    {
        $script = Script::src('https://example.com/chat.js', Category::Preferences, ['async' => true]);

        static::assertSame(
            [Category::Preferences, 'https://example.com/chat.js', null, ['async' => true]],
            [$script->category, $script->src, $script->inline, $script->attributes],
        );
    }

    #[Test]
    public function refusesAnInlineScriptWithoutCode(): void
    {
        $this->expectException(InvalidScriptException::class);
        $this->expectExceptionMessage('An inline script needs code');

        Script::inline('', Category::Analytics);
    }

    #[Test]
    public function refusesAScriptWithoutASrc(): void
    {
        $this->expectException(InvalidScriptException::class);
        $this->expectExceptionMessage('A script loaded from a URL needs its src');

        Script::src('', Category::Analytics);
    }

    #[Test]
    #[DataProvider('closingCode')]
    public function refusesInlineCodeThatWouldCloseItsElement(string $code): void
    {
        $this->expectException(InvalidScriptException::class);
        $this->expectExceptionMessage('An inline script may not contain "</script"');

        Script::inline($code, Category::Analytics);
    }
}
