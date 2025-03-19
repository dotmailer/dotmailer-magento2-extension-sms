<?php

namespace Dotdigitalgroup\Sms\Plugin\Setup\Install;

use Dotdigitalgroup\Email\Setup\Install\DataMigrationTypeProvider as EmailDataMigrationTypeProvider;
use Dotdigitalgroup\Email\Setup\SchemaInterface;
use Dotdigitalgroup\Sms\Setup\Install\Type\RestoreEmailContactTableSmsSubscribers;

class DataMigrationTypeProvider
{
    /**
     * @var RestoreEmailContactTableSmsSubscribers
     */
    private $restoreEmailContactTableSmsSubscribers;

    /**
     * @param RestoreEmailContactTableSmsSubscribers $restoreEmailContactTableSmsSubscribers
     */
    public function __construct(
        RestoreEmailContactTableSmsSubscribers $restoreEmailContactTableSmsSubscribers
    ) {
        $this->restoreEmailContactTableSmsSubscribers = $restoreEmailContactTableSmsSubscribers;
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
        return empty($table) || $table === SchemaInterface::EMAIL_CONTACT_TABLE ?
            $result + [$this->restoreEmailContactTableSmsSubscribers] :
            $result;
    }
}
