<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Consent\Model\Privacy;

use Magento\Customer\Api\Data\CustomerInterface;
use Zero\Consent\Api\PersonalDataInterface;

/**
 * The customer's personal data, section by section (di.xml: argument
 * "sections", code => PersonalDataInterface).
 */
class Sections
{
    /**
     * @param PersonalDataInterface[] $sections
     */
    public function __construct(
        private readonly array $sections = []
    ) {
    }

    /**
     * @return array<string, array<int|string, mixed>> code => data, sections with data only
     */
    public function export(CustomerInterface $customer): array
    {
        $data = [];
        foreach ($this->all() as $code => $section) {
            $value = $section->export($customer);
            if ($value) {
                $data[$code] = $value;
            }
        }

        return $data;
    }

    public function erase(CustomerInterface $customer): void
    {
        foreach ($this->all() as $section) {
            $section->erase($customer);
        }
    }

    /**
     * @return array<string, PersonalDataInterface>
     */
    private function all(): array
    {
        return array_filter($this->sections, static fn ($section) => $section instanceof PersonalDataInterface);
    }
}
