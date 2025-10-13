<?php

namespace Dotdigitalgroup\Sms\Plugin\Setup\Install;

use Dotdigitalgroup\Email\Setup\Install\DataMigrationTypeProvider as EmailDataMigrationTypeProvider;
use Dotdigitalgroup\Email\Setup\SchemaInterface;
use Dotdigitalgroup\Sms\Setup\Install\Type\RestoreEmailContactTableSmsSubscribers;
use Dotdigitalgroup\Sms\Setup\Install\Type\RestoreEmailOrderTableSmsOptIn;

class DataMigrationTypeProvider
{
    /**
     * @var RestoreEmailContactTableSmsSubscribers
     */
    private $restoreEmailContactTableSmsSubscribers;

    /**
     * @var RestoreEmailOrderTableSmsOptIn
     */
    private $restoreEmailOrderTableSmsOptIn;

    /**
     * @param RestoreEmailContactTableSmsSubscribers $restoreEmailContactTableSmsSubscribers
     * @param RestoreEmailOrderTableSmsOptIn $restoreEmailOrderTableSmsOptIn
     */
    public function __construct(
        RestoreEmailContactTableSmsSubscribers $restoreEmailContactTableSmsSubscribers,
        RestoreEmailOrderTableSmsOptIn $restoreEmailOrderTableSmsOptIn
    ) {
        $this->restoreEmailContactTableSmsSubscribers = $restoreEmailContactTableSmsSubscribers;
        $this->restoreEmailOrderTableSmsOptIn = $restoreEmailOrderTableSmsOptIn;
    }

    /**
     * Add SMS migration types.
     *
     * @param EmailDataMigrationTypeProvider $provider
     * @param mixed $result
     * @param string|null $table
     *
     * @return array
     */
    public function afterGetTypes(
        EmailDataMigrationTypeProvider $provider,
        $result,
        ?string $table = null
    ) {
        if (empty($table) || $table === SchemaInterface::EMAIL_CONTACT_TABLE) {
            $result[] = $this->restoreEmailContactTableSmsSubscribers;
        }

        if (empty($table) || $table === SchemaInterface::EMAIL_ORDER_TABLE) {
            $result[] = $this->restoreEmailOrderTableSmsOptIn;
        }

        return $result;
    }
}
