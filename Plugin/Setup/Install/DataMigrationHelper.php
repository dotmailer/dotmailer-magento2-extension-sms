<?php

namespace Dotdigitalgroup\Sms\Plugin\Setup\Install;

use Dotdigitalgroup\Email\Setup\Install\DataMigrationHelper as EmailDataMigrationHelper;
use Dotdigitalgroup\Email\Setup\SchemaInterface;
use Dotdigitalgroup\Sms\Setup\Install\SmsSubscribersTable;
use Dotdigitalgroup\Sms\Setup\Install\Type\BackupEmailContactTableSmsSubscribers;

class DataMigrationHelper
{
    /**
     * @var SmsSubscribersTable
     */
    private $temporaryTable;

    /**
     * @var BackupEmailContactTableSmsSubscribers
     */
    private $backupEmailContactTableSmsSubscribers;

    /**
     * @param SmsSubscribersTable $temporaryTable
     * @param BackupEmailContactTableSmsSubscribers $backupEmailContactTableSmsSubscribers
     */
    public function __construct(
        SmsSubscribersTable $temporaryTable,
        BackupEmailContactTableSmsSubscribers $backupEmailContactTableSmsSubscribers
    ) {
        $this->temporaryTable = $temporaryTable;
        $this->backupEmailContactTableSmsSubscribers = $backupEmailContactTableSmsSubscribers;
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
    public function beforeEmptyTables(EmailDataMigrationHelper $migrationHelper, string $table = null)
    {
        if (empty($table) || $table === SchemaInterface::EMAIL_CONTACT_TABLE) {
            $this->temporaryTable->create();
            $backupStep = $this->backupEmailContactTableSmsSubscribers->execute();
            if ($backupStep->getRowsAffected()) {
                $migrationHelper->logActions($backupStep);
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
        $this->temporaryTable->drop();
        return $result;
    }
}
