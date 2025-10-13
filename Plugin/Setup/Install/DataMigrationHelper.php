<?php

namespace Dotdigitalgroup\Sms\Plugin\Setup\Install;

use Dotdigitalgroup\Email\Setup\Install\DataMigrationHelper as EmailDataMigrationHelper;
use Dotdigitalgroup\Email\Setup\SchemaInterface;
use Dotdigitalgroup\Sms\Setup\Install\SmsSubscribersTable;
use Dotdigitalgroup\Sms\Setup\Install\SmsOrdersOptInTable;
use Dotdigitalgroup\Sms\Setup\Install\Type\BackupEmailContactTableSmsSubscribers;
use Dotdigitalgroup\Sms\Setup\Install\Type\BackupEmailOrderTableSmsOptIn;

class DataMigrationHelper
{
    /**
     * @var SmsSubscribersTable
     */
    private $tempSmsSubscribersTable;

    /**
     * @var SmsOrdersOptInTable
     */
    private $tempSmsOrdersOptInTable;

    /**
     * @var BackupEmailContactTableSmsSubscribers
     */
    private $backupEmailContactTableSmsSubscribers;

    /**
     * @var BackupEmailOrderTableSmsOptIn
     */
    private $backupEmailOrderTableSmsOptIn;

    /**
     * @param SmsSubscribersTable $tempSmsSubscribersTable
     * @param SmsOrdersOptInTable $tempSmsOrdersOptInTable
     * @param BackupEmailContactTableSmsSubscribers $backupEmailContactTableSmsSubscribers
     * @param BackupEmailOrderTableSmsOptIn $backupEmailOrderTableSmsOptIn
     */
    public function __construct(
        SmsSubscribersTable $tempSmsSubscribersTable,
        SmsOrdersOptInTable $tempSmsOrdersOptInTable,
        BackupEmailContactTableSmsSubscribers $backupEmailContactTableSmsSubscribers,
        BackupEmailOrderTableSmsOptIn $backupEmailOrderTableSmsOptIn
    ) {
        $this->tempSmsSubscribersTable = $tempSmsSubscribersTable;
        $this->tempSmsOrdersOptInTable = $tempSmsOrdersOptInTable;
        $this->backupEmailContactTableSmsSubscribers = $backupEmailContactTableSmsSubscribers;
        $this->backupEmailOrderTableSmsOptIn = $backupEmailOrderTableSmsOptIn;
    }

    /**
     * Before emptying tables at the start of migration.
     *
     * @param EmailDataMigrationHelper $migrationHelper
     * @param string $table
     *
     * @return void
     * @throws \Zend_Db_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function beforeEmptyTables(EmailDataMigrationHelper $migrationHelper, ?string $table = null)
    {
        if (empty($table) || $table === SchemaInterface::EMAIL_CONTACT_TABLE) {
            $this->tempSmsSubscribersTable->create();
            $backupStep = $this->backupEmailContactTableSmsSubscribers->execute();
            if ($backupStep->getRowsAffected()) {
                $migrationHelper->logActions($backupStep);
            }
        }

        if (empty($table) || $table === SchemaInterface::EMAIL_ORDER_TABLE) {
            $this->tempSmsOrdersOptInTable->create();

            $backupOrderStep = $this->backupEmailOrderTableSmsOptIn->execute();
            if ($backupOrderStep->getRowsAffected()) {
                $migrationHelper->logActions($backupOrderStep);
            }
        }
    }

    /**
     * After completion of all migration steps.
     *
     * @param EmailDataMigrationHelper $migrationHelper
     * @param mixed $result
     *
     * @return void
     */
    public function afterRun(EmailDataMigrationHelper $migrationHelper, $result)
    {
        $this->tempSmsSubscribersTable->drop();
        $this->tempSmsOrdersOptInTable->drop();
        return $result;
    }
}
