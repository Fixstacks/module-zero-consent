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
 * Product reviews the customer wrote. Erasing keeps the review (it is about
 * the product) but takes the customer off it: no account, the nickname
 * "Anonymous".
 */
class Reviews implements PersonalDataInterface
{
    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    public function export(CustomerInterface $customer): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(['d' => $this->resource->getTableName('review_detail')], ['nickname', 'title', 'detail'])
            ->join(['r' => $this->resource->getTableName('review')], 'r.review_id = d.review_id', ['created_at'])
            ->joinLeft(
                ['p' => $this->resource->getTableName('catalog_product_entity')],
                'p.entity_id = r.entity_pk_value',
                ['product_sku' => 'sku']
            )
            ->where('d.customer_id = ?', (int) $customer->getId())
            ->order('r.created_at ASC');

        return $connection->fetchAll($select);
    }

    public function erase(CustomerInterface $customer): void
    {
        $this->resource->getConnection()->update(
            $this->resource->getTableName('review_detail'),
            ['customer_id' => null, 'nickname' => Orders::ANONYMOUS],
            ['customer_id = ?' => (int) $customer->getId()]
        );
    }
}
