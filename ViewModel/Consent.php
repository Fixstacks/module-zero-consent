<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Consent\ViewModel;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Zero\Consent\Model\Config;
use Zero\Consent\Model\Trackers;

/**
 * The page's consent pieces: settings and consent.js (head, whenever a
 * tracker is on), the banner and the footer links (when there's something to
 * choose: asking, or the Do Not Sell link). The same for every visitor, so
 * cached pages carry them; consent.js decides what to show.
 */
class Consent implements ArgumentInterface
{
    /** @var array<string, string[]>|null */
    private ?array $purposes = null;

    public function __construct(
        private readonly Config $config,
        private readonly Trackers $trackers,
        private readonly UrlInterface $url
    ) {
    }

    /**
     * @return array<string, string[]> analytics|marketing => what the trackers use them for
     */
    public function purposes(): array
    {
        return $this->purposes ??= $this->trackers->purposes();
    }

    /**
     * consent.js and its settings: a tracker is on.
     */
    public function isActive(): bool
    {
        return $this->purposes() !== [];
    }

    /**
     * The banner and Cookie Settings: there's something to choose.
     */
    public function hasChoices(): bool
    {
        return $this->isActive() && ($this->config->asks() || $this->config->showsDoNotSell());
    }

    public function showsDoNotSell(): bool
    {
        return $this->isActive() && $this->config->showsDoNotSell() && isset($this->purposes()['marketing']);
    }

    public function privacyUrl(): string
    {
        $url = $this->config->privacyUrl();

        return str_starts_with($url, '/') ? $this->url->getDirectUrl(ltrim($url, '/')) : $url;
    }

    /**
     * For consent.js (#zero-consent-settings).
     *
     * @return array{ask: bool, gpc: bool}
     */
    public function settings(): array
    {
        return ['ask' => $this->config->asks(), 'gpc' => $this->config->honorsGpc()];
    }
}
