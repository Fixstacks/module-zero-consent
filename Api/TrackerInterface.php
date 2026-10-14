<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Consent\Api;

/**
 * A storefront tracker that needs the visitor's consent. Registered with
 * Zero\Consent\Model\Trackers in di.xml; in the browser it reads
 * window.zeroConsent.get() and listens for the `zero:consent` event.
 */
interface TrackerInterface
{
    public const ANALYTICS = 'analytics';
    public const MARKETING = 'marketing';

    /**
     * On in the current store view.
     */
    public function isActive(): bool;

    /**
     * What it uses cookies for, as the banner shows it under each purpose.
     *
     * @return array<string, \Magento\Framework\Phrase|string> ANALYTICS|MARKETING => description
     */
    public function purposes(): array;
}
