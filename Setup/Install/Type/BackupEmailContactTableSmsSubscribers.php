<?php

namespace Dotdigitalgroup\Sms\Setup\Install\Type;

use Dotdigitalgroup\Email\Setup\Install\Type\AbstractBatchInserter;
use Dotdigitalgroup\Email\Setup\Install\Type\InsertTypeInterface;
use Dotdigitalgroup\Email\Setup\SchemaInterface as EmailSchemaInterface;
use Dotdigitalgroup\Sms\Setup\SchemaInterface as SmsSchemaInterface;

class BackupEmailContactTableSmsSubscribers extends AbstractBatchInserter implements InsertTypeInterface
{
    /**
     * @var string
     */
    protected $tableName = SmsSchemaInterface::SMS_SUBSCRIBERS_TEMPORARY_TABLE;

    /**
     * @inheritdoc
     */
    protected function getSelectStatement()
    {
        return $this->resourceConnection
            ->getConnection()
            ->select()
            ->from(
                $this->resourceConnection->getTableName(EmailSchemaInterface::EMAIL_CONTACT_TABLE),
                [
                    'email',
                    'customer_id',
                    'website_id',
                    'store_id',
                    'mobile_number',
                    'sms_subscriber_status',
                    'sms_change_status_at'
                ]
            )
            ->where('mobile_number is ?', new \Zend_Db_Expr('not null'))
            ->where('mobile_number != ?', trim(''));
    }

    /**
     * @inheritdoc
     */
    public function getInsertArray()
    {
        return [
            'email',
            'customer_id',
            'website_id',
            'store_id',
            'mobile_number',
            'sms_subscriber_status',
            'sms_change_status_at'
        ];
    }
}
