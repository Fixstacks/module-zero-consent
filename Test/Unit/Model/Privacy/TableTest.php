<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Consent\Test\Unit\Model\Privacy;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use PHPUnit\Framework\TestCase;
use Zero\Consent\Model\Privacy\Section\Table;

class TableTest extends TestCase
{
    /** @var string[] */
    private array $deleted = [];

    private function section(string $customerColumn, string $emailColumn, string $pattern = ''): Table
    {
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('isTableExists')->willReturn(true);
        $connection->method('quoteIdentifier')->willReturnCallback(static fn ($name) => '`' . $name . '`');
        $connection->method('quoteInto')->willReturnCallback(
            static fn ($text, $value) => str_replace('?', is_int($value) ? (string) $value : "'" . $value . "'", $text)
        );
        $connection->method('delete')->willReturnCallback(function ($table, $where) {
            $this->deleted[] = $where;

            return 1;
        });
        $resource = $this->createStub(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);

        return new Table($resource, 'zero_example', ['email'], $customerColumn, $emailColumn, $pattern);
    }

    private function customer(int $id, string $email): CustomerInterface
    {
        $customer = $this->createStub(CustomerInterface::class);
        $customer->method('getId')->willReturn($id);
        $customer->method('getEmail')->willReturn($email);

        return $customer;
    }

    public function testRowsAreTheCustomersByIdOrEmail(): void
    {
        $this->section('customer_id', 'email')->erase($this->customer(7, 'a@b.com'));

        $this->assertSame(["(`customer_id` = 7 OR `email` = 'a@b.com')"], $this->deleted);
    }

    public function testAnEmailInsideAValueMatchesThePatternOnly(): void
    {
        $this->section('', 'recipients', '"email":"%s"')->erase($this->customer(7, 'a_b@c.com'));

        $this->assertSame(["(`recipients` LIKE '%\"email\":\"a\\_b@c.com\"%')"], $this->deleted);
    }

    public function testNothingToMatchOnMatchesNothing(): void
    {
        $this->section('', 'email')->erase($this->customer(7, ''));

        $this->assertSame(['1 = 0'], $this->deleted);
    }
}
