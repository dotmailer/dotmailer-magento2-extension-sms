<?php

namespace Dotdigitalgroup\Sms\Setup\Install\Type;

use Dotdigitalgroup\Email\Setup\Install\Type\AbstractBulkUpdater;
use Dotdigitalgroup\Email\Setup\Install\Type\BulkUpdateTypeInterface;
use Dotdigitalgroup\Email\Setup\SchemaInterface as EmailSchemaInterface;
use Dotdigitalgroup\Sms\Setup\SchemaInterface as SmsSchemaInterface;

class RestoreEmailContactTableSmsSubscribers extends AbstractBulkUpdater implements BulkUpdateTypeInterface
{
    /**
     * @var string
     */
    protected $tableName = EmailSchemaInterface::EMAIL_CONTACT_TABLE;

    /**
     * @inheritdoc
     */
    protected function getSelectStatement()
    {
        return $this->resourceConnection
            ->getConnection()
            ->select()
            ->from(
                $this->resourceConnection->getTableName(SmsSchemaInterface::SMS_SUBSCRIBERS_TEMPORARY_TABLE),
                [
                    'email',
                    'customer_id',
                    'website_id',
                    'store_id',
                    'mobile_number',
                    'sms_subscriber_status',
                    'sms_change_status_at'
                ]
            );
    }

    /**
     * @inheritdoc
     */
    public function getUpdateBindings($bind)
    {
        return [
            'mobile_number' => $bind['mobile_number'] ?? null,
            'sms_subscriber_status' => $bind['sms_subscriber_status'] ?? null,
            'sms_change_status_at' => $bind['sms_change_status_at'] ?? null,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getUpdateWhereClause($row)
    {
        return [
            'email = ?' => $row['email'],
            'customer_id = ?' => $row['customer_id'],
            'website_id = ?' => $row['website_id'],
            'store_id = ?' => $row['store_id'],
        ];
    }

    /**
     * Get bind key.
     *
     * This migration type does not use a bind key (that references a single value)
     * because it must update mobile_number _and_ sms_subscriber_status.
     */
    public function getBindKey(): string
    {
        return '';
    }
}
