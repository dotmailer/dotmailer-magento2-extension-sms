<?php

namespace Dotdigitalgroup\Sms\Plugin;

use Dotdigitalgroup\Email\Model\Cron\Cleaner;
use Dotdigitalgroup\Sms\Setup\SchemaInterface;

class CronCleanerPlugin
{
    /**
     * @var array
     */
    private $tables = [
        'sms_order_queue' => SchemaInterface::EMAIL_SMS_ORDER_QUEUE_TABLE
    ];

    /**
     * Add SMS tables to the list of tables to be cleaned up.
     *
     * @param Cleaner $cleaner
     * @param array $additionalTables
     * @return array
     */
    public function beforeGetTablesForCleanUp(Cleaner $cleaner, array $additionalTables = []): array
    {
        return [
            '$additionalTables' => $this->tables,
        ];
    }
}
