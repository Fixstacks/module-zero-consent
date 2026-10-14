<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Consent\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Stores > Configuration > Zero > Cookie Consent, for the current store view.
 */
class Config
{
    private const PATH = 'zero_consent/general/';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function asks(): bool
    {
        return $this->flag('ask');
    }

    public function honorsGpc(): bool
    {
        return $this->flag('gpc');
    }

    public function showsDoNotSell(): bool
    {
        return $this->flag('do_not_sell');
    }

    /**
     * An absolute URL or a path; '' when empty or anything else (javascript:…).
     */
    public function privacyUrl(): string
    {
        $url = trim((string) $this->scopeConfig->getValue(self::PATH . 'privacy_url', ScopeInterface::SCOPE_STORE));

        return preg_match('#^(https?://|/)#i', $url) ? $url : '';
    }

    private function flag(string $field): bool
    {
        return $this->scopeConfig->isSetFlag(self::PATH . $field, ScopeInterface::SCOPE_STORE);
    }
}
