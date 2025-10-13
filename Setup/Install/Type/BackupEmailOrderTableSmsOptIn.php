<?php

namespace Dotdigitalgroup\Sms\Setup\Install\Type;

use Dotdigitalgroup\Email\Setup\Install\Type\AbstractBatchInserter;
use Dotdigitalgroup\Email\Setup\Install\Type\InsertTypeInterface;
use Dotdigitalgroup\Email\Setup\SchemaInterface as EmailSchemaInterface;
use Dotdigitalgroup\Sms\Setup\SchemaInterface as SmsSchemaInterface;

class BackupEmailOrderTableSmsOptIn extends AbstractBatchInserter implements InsertTypeInterface
{
    /**
     * @var string
     */
    protected $tableName = SmsSchemaInterface::SMS_ORDERS_OPTIN_TEMPORARY_TABLE;

    /**
     * @inheritdoc
     */
    protected function getSelectStatement()
    {
        return $this->resourceConnection
            ->getConnection()
            ->select()
            ->from(
                $this->resourceConnection->getTableName(EmailSchemaInterface::EMAIL_ORDER_TABLE),
                [
                    'order_id',
                    'store_id',
                    'sms_transactional_requires_opt_in',
                    'sms_transactional_opt_in'
                ]
            )
            ->where('sms_transactional_requires_opt_in != ?', 0)
            ->orWhere('sms_transactional_opt_in != ?', 0);
    }

    /**
     * @inheritdoc
     */
    public function getInsertArray()
    {
        return [
            'order_id',
            'store_id',
            'sms_transactional_requires_opt_in',
            'sms_transactional_opt_in'
        ];
    }
}
