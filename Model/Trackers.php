<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Consent\Model;

use Zero\Consent\Api\TrackerInterface;

/**
 * The trackers asking for consent (di.xml: argument "trackers").
 */
class Trackers
{
    /**
     * @param TrackerInterface[] $trackers
     */
    public function __construct(
        private readonly array $trackers = []
    ) {
    }

    /**
     * What the active trackers use cookies for, by purpose (analytics first).
     *
     * @return array<string, string[]>
     */
    public function purposes(): array
    {
        $purposes = [TrackerInterface::ANALYTICS => [], TrackerInterface::MARKETING => []];
        foreach ($this->trackers as $tracker) {
            if (!$tracker instanceof TrackerInterface || !$tracker->isActive()) {
                continue;
            }
            foreach ($tracker->purposes() as $purpose => $description) {
                if (isset($purposes[$purpose])) {
                    $purposes[$purpose][] = (string) $description;
                }
            }
        }

        return array_filter($purposes);
    }
}
