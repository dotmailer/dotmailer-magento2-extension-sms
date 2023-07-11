<?php

namespace Dotdigitalgroup\Sms\Setup\Install;

use Dotdigitalgroup\Sms\Setup\SchemaInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Ddl\Table;

class SmsSubscribersTable
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Create temporary table.
     *
     * @return void
     * @throws \Zend_Db_Exception
     */
    public function create()
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->newTable(SchemaInterface::SMS_SUBSCRIBERS_TEMPORARY_TABLE);
        $table->addColumn(
            'email',
            Table::TYPE_TEXT,
            255,
            ['unsigned' => true, 'nullable' => true],
            'Email'
        );
        $table->addColumn(
            'customer_id',
            Table::TYPE_INTEGER,
            10,
            ['unsigned' => true, 'nullable' => false],
            'Customer ID'
        );
        $table->addColumn(
            'website_id',
            Table::TYPE_SMALLINT,
            5,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Website ID'
        );
        $table->addColumn(
            'store_id',
            Table::TYPE_SMALLINT,
            5,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Store ID'
        );
        $table->addColumn(
            'mobile_number',
            Table::TYPE_TEXT,
            255,
            ['unsigned' => true, 'nullable' => true],
            'Mobile number'
        );
        $table->addColumn(
            'sms_subscriber_status',
            Table::TYPE_SMALLINT,
            5,
            ['unsigned' => true, 'nullable' => true],
            'SMS subscriber status'
        );
        $table->addColumn(
            'sms_change_status_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => true],
            'SMS subscriber status last changed date'
        );
        $connection->dropTemporaryTable(SchemaInterface::SMS_SUBSCRIBERS_TEMPORARY_TABLE);
        $connection->createTemporaryTable($table);
    }

    /**
     * Drop table.
     *
     * @return void
     */
    public function drop()
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->dropTemporaryTable(SchemaInterface::SMS_SUBSCRIBERS_TEMPORARY_TABLE);
    }
}
