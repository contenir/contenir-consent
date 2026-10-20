<?php

declare(strict_types=1);

namespace Contenir\Consent;

/**
 * How Google's tags meet consent (Google Consent Mode v2).
 *
 * - Basic: Google's tags don't load until the visitor allows them, like any
 *   other script. The default.
 * - Advanced: Google's tags load at once with every signal denied, sending
 *   Google cookieless pings it models conversions from, and switch to full
 *   measurement when the visitor allows them.
 *
 * Either way, the defaults are denied before any tag loads, and the stored
 * choice updates them on every page.
 *
 * @api
 */
enum GoogleConsentMode: string
{
    case Advanced = 'advanced';
    case Basic    = 'basic';

    /**
     * The mode's name in the settings.
     */
    public function label(): string
    {
        return match ($this) {
            self::Advanced => 'Advanced: load Google tags at once, without cookies until visitors allow them',
            self::Basic => 'Basic: load Google tags only once visitors allow them',
        };
    }
}
