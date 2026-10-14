<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Consent\Setup\Patch\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * The banner's settings moved here from Zero_GoogleAnalytics, at every scope:
 * zero_google_analytics/consent/banner → zero_consent/general/ask,
 * zero_google_analytics/consent/privacy_url → zero_consent/general/privacy_url.
 */
class MoveSettingsFromGoogleAnalytics implements DataPatchInterface
{
    private const MOVES = [
        'zero_google_analytics/consent/banner' => 'zero_consent/general/ask',
        'zero_google_analytics/consent/privacy_url' => 'zero_consent/general/privacy_url',
    ];

    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    public function apply(): self
    {
        $db = $this->resource->getConnection();
        $table = $this->resource->getTableName('core_config_data');
        foreach (self::MOVES as $from => $to) {
            $rows = $db->fetchAll($db->select()->from($table, ['scope', 'scope_id', 'value'])->where('path = ?', $from));
            foreach ($rows as $row) {
                $db->insertOnDuplicate($table, $row + ['path' => $to], ['value']);
            }
            $db->delete($table, ['path = ?' => $from]);
        }

        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
