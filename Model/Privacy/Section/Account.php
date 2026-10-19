<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Consent\Model\Privacy\Section;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Zero\Consent\Api\PersonalDataInterface;

/**
 * The account and its saved addresses. Erased with the account itself
 * (Model\Privacy\Eraser deletes the customer last).
 */
class Account implements PersonalDataInterface
{
    public function export(CustomerInterface $customer): array
    {
        return [
            'email' => $customer->getEmail(),
            'prefix' => $customer->getPrefix(),
            'firstname' => $customer->getFirstname(),
            'middlename' => $customer->getMiddlename(),
            'lastname' => $customer->getLastname(),
            'suffix' => $customer->getSuffix(),
            'dob' => $customer->getDob(),
            'gender' => $customer->getGender(),
            'taxvat' => $customer->getTaxvat(),
            'created_at' => $customer->getCreatedAt(),
            'updated_at' => $customer->getUpdatedAt(),
            'addresses' => array_map(static fn (AddressInterface $address) => [
                'firstname' => $address->getFirstname(),
                'lastname' => $address->getLastname(),
                'company' => $address->getCompany(),
                'street' => $address->getStreet(),
                'city' => $address->getCity(),
                'region' => $address->getRegion()?->getRegion(),
                'postcode' => $address->getPostcode(),
                'country_id' => $address->getCountryId(),
                'telephone' => $address->getTelephone(),
                'vat_id' => $address->getVatId(),
                'default_billing' => (bool) $address->isDefaultBilling(),
                'default_shipping' => (bool) $address->isDefaultShipping(),
            ], $customer->getAddresses() ?? []),
        ];
    }

    public function erase(CustomerInterface $customer): void
    {
        // The account and its addresses go when the eraser deletes the customer.
    }
}
