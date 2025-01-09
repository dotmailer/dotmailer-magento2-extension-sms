<?php

namespace Dotdigitalgroup\Sms\Plugin;

use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Email\Model\Integration\IntegrationSetup;
use Magento\Store\Model\StoreManagerInterface;

class IntegrationSetupPlugin
{
    /**
     * @var IntegrationSetup
     */
    private $integrationSetup;

    /**
     * @var Configuration
     */
    private $moduleConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * IntegrationSetupPlugin constructor.
     *
     * @param IntegrationSetup $IntegrationSetup
     * @param Configuration $moduleConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        IntegrationSetup $IntegrationSetup,
        Configuration $moduleConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->integrationSetup = $IntegrationSetup;
        $this->moduleConfig = $moduleConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Adds SMS Subscriber list to Set Up Integration.
     *
     * @param IntegrationSetup $IntegrationSetup
     * @return void
     */
    public function beforeCreateAddressBooks(IntegrationSetup $IntegrationSetup)
    {
        $addressBookMap = $this->integrationSetup->getAddressBookMap();

        if (!isset($addressBookMap['Magento SMS Subscribers'])) {
            $addressBookMap['Magento SMS Subscribers'] = [
                'visibility' => 'Private',
                'path' => ConfigInterface::XML_PATH_CONNECTOR_SMS_SUBSCRIBER_ADDRESS_BOOK_ID,
            ];

            $this->integrationSetup->setAddressBookMap($addressBookMap);
        }
    }
}
