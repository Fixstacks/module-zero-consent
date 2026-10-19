<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Consent\Model\Privacy\Section;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Zero\Consent\Api\PersonalDataInterface;
use Zero\Consent\Model\Config;

/**
 * The customer's orders: what they bought, when, for how much, and the
 * addresses. Erasing anonymizes them (Config::anonymizesOrders()): the
 * customer's name, email, addresses, phone and IP leave the order, its
 * addresses and the order, invoice, shipment and credit memo grids; amounts,
 * items, country, region and tax stay. Without it they only become guest
 * orders (the foreign key clears customer_id).
 */
class Orders implements PersonalDataInterface
{
    public const ANONYMOUS = 'Anonymous';

    /** Grid table => its columns with the customer's name, email or address. */
    private const GRIDS = [
        'sales_order_grid' => ['customer_name', 'customer_email', 'billing_name', 'shipping_name', 'billing_address', 'shipping_address'],
        'sales_invoice_grid' => ['customer_name', 'customer_email', 'billing_name', 'billing_address', 'shipping_address'],
        'sales_shipment_grid' => ['customer_name', 'customer_email', 'billing_name', 'shipping_name', 'billing_address', 'shipping_address'],
        'sales_creditmemo_grid' => ['customer_name', 'customer_email', 'billing_name', 'billing_address', 'shipping_address'],
    ];

    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly ResourceConnection $resource,
        private readonly Config $config
    ) {
    }

    public function export(CustomerInterface $customer): array
    {
        $orders = [];
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('customer_id', (int) $customer->getId())
            ->setOrder('created_at', 'ASC');
        /** @var Order $order */
        foreach ($collection as $order) {
            $orders[] = [
                'number' => $order->getIncrementId(),
                'created_at' => $order->getCreatedAt(),
                'status' => $order->getStatus(),
                'currency' => $order->getOrderCurrencyCode(),
                'grand_total' => (float) $order->getGrandTotal(),
                'email' => $order->getCustomerEmail(),
                'remote_ip' => $order->getRemoteIp(),
                'items' => array_map(static fn ($item) => [
                    'sku' => $item->getSku(),
                    'name' => $item->getName(),
                    'qty' => (float) $item->getQtyOrdered(),
                    'price' => (float) $item->getPrice(),
                ], array_values($order->getAllVisibleItems())),
                'addresses' => array_map(static fn (Address $address) => [
                    'type' => $address->getAddressType(),
                    'name' => trim($address->getFirstname() . ' ' . $address->getLastname()),
                    'company' => $address->getCompany(),
                    'street' => $address->getStreet(),
                    'city' => $address->getCity(),
                    'region' => $address->getRegion(),
                    'postcode' => $address->getPostcode(),
                    'country_id' => $address->getCountryId(),
                    'telephone' => $address->getTelephone(),
                    'email' => $address->getEmail(),
                ], array_values($order->getAddresses())),
            ];
        }

        return $orders;
    }

    public function erase(CustomerInterface $customer): void
    {
        $connection = $this->resource->getConnection();
        $orderTable = $this->resource->getTableName('sales_order');
        $orderIds = array_map('intval', $connection->fetchCol(
            $connection->select()->from($orderTable, ['entity_id'])->where('customer_id = ?', (int) $customer->getId())
        ));
        if (!$orderIds) {
            return;
        }
        $connection->update($orderTable, ['customer_id' => null, 'customer_is_guest' => 1], ['entity_id IN (?)' => $orderIds]);
        if (!$this->config->anonymizesOrders()) {
            return;
        }
        $connection->update($orderTable, [
            'customer_email' => null,
            'customer_prefix' => null,
            'customer_firstname' => self::ANONYMOUS,
            'customer_middlename' => null,
            'customer_lastname' => null,
            'customer_suffix' => null,
            'customer_dob' => null,
            'customer_taxvat' => null,
            'customer_gender' => null,
            'remote_ip' => null,
            'x_forwarded_for' => null,
        ], ['entity_id IN (?)' => $orderIds]);
        $connection->update($this->resource->getTableName('sales_order_address'), [
            'customer_id' => null,
            'customer_address_id' => null,
            'prefix' => null,
            'firstname' => self::ANONYMOUS,
            'middlename' => null,
            'lastname' => null,
            'suffix' => null,
            'company' => null,
            'street' => null,
            'city' => null,
            'postcode' => null,
            'telephone' => null,
            'fax' => null,
            'email' => null,
            'vat_id' => null,
        ], ['parent_id IN (?)' => $orderIds]);
        foreach (self::GRIDS as $grid => $columns) {
            $table = $this->resource->getTableName($grid);
            if (!$connection->isTableExists($table)) {
                continue;
            }
            $existing = $connection->describeTable($table);
            $values = [];
            foreach ($columns as $column) {
                if (isset($existing[$column])) {
                    $values[$column] = str_ends_with($column, '_name') ? self::ANONYMOUS : ($existing[$column]['NULLABLE'] ? null : '');
                }
            }
            $where = $grid === 'sales_order_grid' ? ['entity_id IN (?)' => $orderIds] : ['order_id IN (?)' => $orderIds];
            if (isset($existing['customer_id'])) {
                $values['customer_id'] = null;
            }
            $connection->update($table, $values, $where);
        }
    }
}
