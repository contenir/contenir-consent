<?php

declare(strict_types=1);

namespace Contenir\Consent\Tests\Unit;

use Contenir\Consent\GoogleConsentMode;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class GoogleConsentModeTest extends TestCase
{
    #[Test]
    public function namesEachModeForTheSettings(): void
    {
        static::assertSame(
            [
                'Basic: load Google tags only once visitors allow them',
                'Advanced: load Google tags at once, without cookies until visitors allow them',
            ],
            [GoogleConsentMode::Basic->label(), GoogleConsentMode::Advanced->label()],
        );
    }
}
