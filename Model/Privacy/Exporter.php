<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Consent\Model\Privacy;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Everything the store keeps about a customer, as one JSON document (GDPR
 * art. 15 and 20: readable, and portable to another service).
 */
class Exporter
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly Sections $sections,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function json(int $customerId): string
    {
        $customer = $this->customerRepository->getById($customerId);
        $store = $this->storeManager->getStore();

        return (string) json_encode([
            'store' => $store->getFrontendName(),
            'url' => $store->getBaseUrl(),
            'exported_at' => gmdate('c'),
            'data' => $this->sections->export($customer),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    public function fileName(): string
    {
        return 'my-data-' . gmdate('Y-m-d') . '.json';
    }
}
