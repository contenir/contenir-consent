<?php

declare(strict_types=1);

namespace Contenir\Consent\Tests\Unit\Internal;

use Contenir\Consent\Internal\Js;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class JsTest extends TestCase
{
    #[Test]
    public function writesValuesThatCannotEndTheirScriptElement(): void
    {
        static::assertSame(
            <<<'JSON'
                {"text":"\u003C/script\u003E \u0026 \u0027quotes\u0022","url":"https://example.com/a","name":"Zoë","on":true}
                JSON,
            Js::value([
                'text' => '</script> & \'quotes"',
                'url'  => 'https://example.com/a',
                'name' => 'Zoë',
                'on'   => true,
            ]),
        );
    }
}
