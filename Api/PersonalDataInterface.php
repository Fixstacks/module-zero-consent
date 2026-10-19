<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Consent\Api;

use Magento\Customer\Api\Data\CustomerInterface;

/**
 * One kind of personal data the store keeps about a customer (their orders,
 * reviews, newsletter subscription, …), for My Account > Privacy. Registered
 * with Zero\Consent\Model\Privacy\Sections in di.xml under a code, which
 * names the section in the download.
 */
interface PersonalDataInterface
{
    /**
     * What the customer's download holds for this section: lists and values
     * only (JSON). [] when there is nothing.
     *
     * @return array<int|string, mixed>
     */
    public function export(CustomerInterface $customer): array;

    /**
     * Deletes or anonymizes it, before the account itself is deleted. Runs in
     * the eraser's database transaction.
     */
    public function erase(CustomerInterface $customer): void;
}
