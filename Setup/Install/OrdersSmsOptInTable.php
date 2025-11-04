<?php

namespace Dotdigitalgroup\Sms\Setup\Install;

use Dotdigitalgroup\Sms\Setup\SchemaInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Ddl\Table;

class OrdersSmsOptInTable
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
        $table = $connection->newTable(SchemaInterface::SMS_ORDERS_OPTIN_TEMPORARY_TABLE);
        $table->addColumn(
            'order_id',
            Table::TYPE_INTEGER,
            10,
            ['unsigned' => true, 'nullable' => false],
            'Order ID'
        );
        $table->addColumn(
            'store_id',
            Table::TYPE_SMALLINT,
            5,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Store ID'
        );
        $table->addColumn(
            'sms_transactional_requires_opt_in',
            Table::TYPE_SMALLINT,
            5,
            ['unsigned' => true, 'nullable' => true],
            'SMS transactional requires opt in'
        );
        $table->addColumn(
            'sms_transactional_opt_in',
            Table::TYPE_SMALLINT,
            5,
            ['unsigned' => true, 'nullable' => true],
            'SMS transactional opt in'
        );
        $connection->dropTemporaryTable(SchemaInterface::SMS_ORDERS_OPTIN_TEMPORARY_TABLE);
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
        $connection->dropTemporaryTable(SchemaInterface::SMS_ORDERS_OPTIN_TEMPORARY_TABLE);
    }
}
