<?php declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateSmsConsentConfiguration implements DataPatchInterface
{
    public const LEGACY_CONSENT_PATH = 'connector_consent/sms/enabled';

    public const NEW_CONSENT_PATHS = [
        'connector_consent/sms/account_enabled',
        'connector_consent/sms/checkout_enabled',
        'connector_consent/sms/registration_enabled',
    ];

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * UpdateSmsConsentConfiguration constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $currentRecordSelectQuery = $this->moduleDataSetup
            ->getConnection()
            ->select()->from(
                $this->moduleDataSetup->getTable('core_config_data'),
                ['*']
            )->where(
                "path LIKE '%".static::LEGACY_CONSENT_PATH."%'"
            );
        $currentRecordSets = $this->moduleDataSetup
            ->getConnection()
            ->fetchAll($currentRecordSelectQuery);
        foreach ($currentRecordSets as $currentRecordSet) {
            $this->updateCurrentIterationRecordSet($currentRecordSet);
            $this->removeCurrentIterationLegacyRecordSet($currentRecordSet);
        }
        $this->moduleDataSetup->endSetup();
        return $this;
    }

    /**
     * Update the current record set.
     *
     * Apply the legacy records value to the current iteration record
     * set using the 3 new paths available.
     *
     * @param array $currentRecordSet
     * @return void
     */
    private function updateCurrentIterationRecordSet($currentRecordSet)
    {
        foreach (static::NEW_CONSENT_PATHS as $newConsentPath) {
            $this->moduleDataSetup->getConnection()->insert(
                $this->moduleDataSetup->getTable('core_config_data'),
                [
                    'scope' => $currentRecordSet['scope'],
                    'scope_id' => $currentRecordSet['scope_id'],
                    'path' => $newConsentPath,
                    'value' => $currentRecordSet['value'] ?? 0,
                ]
            );
        }
    }

    /**
     * Remove the legacy record set.
     *
     * @param array $currentRecordSet
     * @return void
     */
    private function removeCurrentIterationLegacyRecordSet($currentRecordSet)
    {
        $this->moduleDataSetup->getConnection()->delete(
            $this->moduleDataSetup->getTable('core_config_data'),
            [
                'path = ?' => $currentRecordSet['path'],
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }
}
