<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Consent\Model\Privacy\Section;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Newsletter\Model\Subscriber;
use Zero\Consent\Api\PersonalDataInterface;

/**
 * Newsletter subscriptions under the customer's account or email, with
 * their status. Erasing deletes them (also a subscription made as a guest
 * with the same email).
 */
class Newsletter implements PersonalDataInterface
{
    private const STATUSES = [
        Subscriber::STATUS_SUBSCRIBED => 'subscribed',
        Subscriber::STATUS_NOT_ACTIVE => 'not confirmed',
        Subscriber::STATUS_UNSUBSCRIBED => 'unsubscribed',
        Subscriber::STATUS_UNCONFIRMED => 'unconfirmed',
    ];

    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    public function export(CustomerInterface $customer): array
    {
        $connection = $this->resource->getConnection();
        $rows = $connection->fetchAll($this->select($customer, ['store_id', 'subscriber_email', 'subscriber_status', 'change_status_at']));

        return array_map(static fn (array $row) => [
            'email' => $row['subscriber_email'],
            'store_id' => (int) $row['store_id'],
            'status' => self::STATUSES[(int) $row['subscriber_status']] ?? (string) $row['subscriber_status'],
            'changed_at' => $row['change_status_at'],
        ], $rows);
    }

    public function erase(CustomerInterface $customer): void
    {
        $connection = $this->resource->getConnection();
        $ids = $connection->fetchCol($this->select($customer, ['subscriber_id']));
        if ($ids) {
            $connection->delete($this->resource->getTableName('newsletter_subscriber'), ['subscriber_id IN (?)' => $ids]);
        }
    }

    /**
     * @param string[] $columns
     */
    private function select(CustomerInterface $customer, array $columns): \Magento\Framework\DB\Select
    {
        $connection = $this->resource->getConnection();

        return $connection->select()
            ->from($this->resource->getTableName('newsletter_subscriber'), $columns)
            ->where('customer_id = ?', (int) $customer->getId())
            ->orWhere('subscriber_email = ?', (string) $customer->getEmail());
    }
}
