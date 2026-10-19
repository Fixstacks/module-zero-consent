<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Consent\Model\Privacy\Section;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\ResourceConnection;
use Zero\Consent\Api\PersonalDataInterface;

/**
 * Personal data in one table, as di.xml describes it: the rows found by the
 * customer's id (`customerColumn`) or email (`emailColumn`: the exact value,
 * or with `emailPattern` the email inside it, e.g. '"email":"%s"' in a JSON
 * column, so a@b.com doesn't match aa@b.com), the columns
 * the download shows, and how erasing treats the rows: "delete" them, or
 * "anonymize" the `anonymize` columns (column => value; null clears it).
 * A table that doesn't exist (its module is off or not installed) is skipped.
 */
class Table implements PersonalDataInterface
{
    public const DELETE = 'delete';
    public const ANONYMIZE = 'anonymize';
    public const KEEP = 'keep';

    /**
     * @param string[] $columns
     * @param array<string, string|null> $anonymize
     */
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly string $table,
        private readonly array $columns,
        private readonly string $customerColumn = '',
        private readonly string $emailColumn = '',
        private readonly string $emailPattern = '',
        private readonly string $erase = self::DELETE,
        private readonly array $anonymize = []
    ) {
    }

    public function export(CustomerInterface $customer): array
    {
        $table = $this->table();
        if (!$table) {
            return [];
        }
        $connection = $this->resource->getConnection();

        return $connection->fetchAll($connection->select()->from($table, $this->columns)->where($this->condition($customer)));
    }

    public function erase(CustomerInterface $customer): void
    {
        $table = $this->table();
        if (!$table || $this->erase === self::KEEP) {
            return;
        }
        $connection = $this->resource->getConnection();
        if ($this->erase === self::ANONYMIZE) {
            $connection->update($table, $this->anonymize, $this->condition($customer));
        } else {
            $connection->delete($table, $this->condition($customer));
        }
    }

    /**
     * The customer's rows: by id or email; nothing to match on matches nothing.
     */
    private function condition(CustomerInterface $customer): string
    {
        $connection = $this->resource->getConnection();
        $conditions = [];
        if ($this->customerColumn !== '') {
            $conditions[] = $connection->quoteInto($connection->quoteIdentifier($this->customerColumn) . ' = ?', (int) $customer->getId());
        }
        $email = (string) $customer->getEmail();
        if ($this->emailColumn !== '' && $email !== '') {
            $column = $connection->quoteIdentifier($this->emailColumn);
            $conditions[] = $this->emailPattern !== ''
                ? $connection->quoteInto($column . ' LIKE ?', '%' . sprintf($this->emailPattern, addcslashes($email, '%_\\')) . '%')
                : $connection->quoteInto($column . ' = ?', $email);
        }

        return $conditions ? '(' . implode(' OR ', $conditions) . ')' : '1 = 0';
    }

    private function table(): ?string
    {
        $table = $this->resource->getTableName($this->table);

        return $this->resource->getConnection()->isTableExists($table) ? $table : null;
    }
}
