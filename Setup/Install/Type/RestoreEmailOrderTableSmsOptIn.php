<?php

namespace Dotdigitalgroup\Sms\Setup\Install\Type;

use Dotdigitalgroup\Email\Setup\Install\Type\AbstractBulkUpdater;
use Dotdigitalgroup\Email\Setup\Install\Type\BulkUpdateTypeInterface;
use Dotdigitalgroup\Email\Setup\SchemaInterface as EmailSchemaInterface;
use Dotdigitalgroup\Sms\Setup\SchemaInterface as SmsSchemaInterface;

class RestoreEmailOrderTableSmsOptIn extends AbstractBulkUpdater implements BulkUpdateTypeInterface
{
    /**
     * @var string
     */
    protected $tableName = EmailSchemaInterface::EMAIL_ORDER_TABLE;

    /**
     * @inheritdoc
     */
    protected function getSelectStatement()
    {
        return $this->resourceConnection
            ->getConnection()
            ->select()
            ->from(
                $this->resourceConnection->getTableName(SmsSchemaInterface::SMS_ORDERS_OPTIN_TEMPORARY_TABLE),
                [
                    'order_id',
                    'store_id',
                    'sms_transactional_requires_opt_in',
                    'sms_transactional_opt_in'
                ]
            );
    }

    /**
     * @inheritdoc
     */
    public function getUpdateBindings($bind)
    {
        return [
            'sms_transactional_requires_opt_in' => $bind['sms_transactional_requires_opt_in'] ?? null,
            'sms_transactional_opt_in' => $bind['sms_transactional_opt_in'] ?? null,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getUpdateWhereClause($row)
    {
        return [
            'order_id = ?' => $row['order_id'],
            'store_id = ?' => $row['store_id'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getBindKey(): string
    {
        return '';
    }
}
