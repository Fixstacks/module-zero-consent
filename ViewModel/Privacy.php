<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Consent\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Zero\Consent\Model\Config;

/**
 * What My Account > Privacy offers in this store view.
 */
class Privacy implements ArgumentInterface
{
    public function __construct(
        private readonly Config $config
    ) {
    }

    public function allowsDeletion(): bool
    {
        return $this->config->allowsDeletion();
    }

    public function anonymizesOrders(): bool
    {
        return $this->config->anonymizesOrders();
    }

    /** The privacy policy's URL, '' when none is set. */
    public function policyUrl(): string
    {
        return $this->config->privacyUrl();
    }
}
