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
 * Stores > Configuration > Zero > Cookie Consent, for the current store view:
 * the cookie banner and the customer's personal data (My Account > Privacy).
 */
class Config
{
    private const PATH = 'zero_consent/general/';
    private const PRIVACY = 'zero_consent/privacy/';

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

    /** My Account > Privacy is on. */
    public function privacyEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::PRIVACY . 'enabled', ScopeInterface::SCOPE_STORE);
    }

    /** Customers can delete their own account there. */
    public function allowsDeletion(): bool
    {
        return $this->privacyEnabled() && $this->scopeConfig->isSetFlag(self::PRIVACY . 'allow_delete', ScopeInterface::SCOPE_STORE);
    }

    /** A deleted account's orders lose the customer's name, email, addresses and phone. */
    public function anonymizesOrders(): bool
    {
        return $this->scopeConfig->isSetFlag(self::PRIVACY . 'anonymize_orders', ScopeInterface::SCOPE_STORE);
    }

    private function flag(string $field): bool
    {
        return $this->scopeConfig->isSetFlag(self::PATH . $field, ScopeInterface::SCOPE_STORE);
    }
}
