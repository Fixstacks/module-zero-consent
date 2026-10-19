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
 * The wish list's products and the saved carts (quotes, with their
 * products). Erasing deletes both.
 */
class Wishlist implements PersonalDataInterface
{
    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    public function export(CustomerInterface $customer): array
    {
        $connection = $this->resource->getConnection();
        $wishlist = $connection->fetchAll($connection->select()
            ->from(['w' => $this->resource->getTableName('wishlist')], [])
            ->join(['i' => $this->resource->getTableName('wishlist_item')], 'i.wishlist_id = w.wishlist_id', ['qty', 'added_at', 'description'])
            ->joinLeft(['p' => $this->resource->getTableName('catalog_product_entity')], 'p.entity_id = i.product_id', ['sku'])
            ->where('w.customer_id = ?', (int) $customer->getId()));
        $carts = $connection->fetchAll($connection->select()
            ->from(['q' => $this->resource->getTableName('quote')], ['updated_at', 'is_active'])
            ->join(['i' => $this->resource->getTableName('quote_item')], 'i.quote_id = q.entity_id AND i.parent_item_id IS NULL', ['sku', 'name', 'qty'])
            ->where('q.customer_id = ?', (int) $customer->getId()));

        return array_filter(['wishlist' => $wishlist, 'carts' => $carts]);
    }

    public function erase(CustomerInterface $customer): void
    {
        $connection = $this->resource->getConnection();
        $connection->delete($this->resource->getTableName('wishlist'), ['customer_id = ?' => (int) $customer->getId()]);
        $connection->delete($this->resource->getTableName('quote'), ['customer_id = ?' => (int) $customer->getId()]);
    }
}
