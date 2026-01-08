<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Task\TaskRunInterface;
use Dotdigitalgroup\Sms\Model\Apiconnector\SmsClientFactory;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Queue\SmsMessageQueueManager;
use Dotdigitalgroup\Sms\Model\Queue\SenderProgressHandlerFactory;
use Magento\Store\Model\StoreManagerInterface;

class SmsStatusManager implements TaskRunInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var SmsClientFactory
     */
    private $smsClientFactory;

    /**
     * @var Configuration
     */
    private $moduleConfig;

    /**
     * @var SmsMessageQueueManager
     */
    private $smsMessageQueueManager;

    /**
     * @var SenderProgressHandlerFactory
     */
    private $senderProgressHandlerFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * SmsStatusManager constructor.
     *
     * @param Data $helper
     * @param SmsClientFactory $smsClientFactory
     * @param Configuration $moduleConfig
     * @param SmsMessageQueueManager $smsMessageQueueManager
     * @param SenderProgressHandlerFactory $senderProgressHandlerFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Data $helper,
        SmsClientFactory $smsClientFactory,
        Configuration $moduleConfig,
        SmsMessageQueueManager $smsMessageQueueManager,
        SenderProgressHandlerFactory $senderProgressHandlerFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->helper = $helper;
        $this->smsClientFactory = $smsClientFactory;
        $this->moduleConfig = $moduleConfig;
        $this->smsMessageQueueManager = $smsMessageQueueManager;
        $this->senderProgressHandlerFactory = $senderProgressHandlerFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Run SMS status update tasks.
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function run()
    {
        $activeApiUsers = $this->getAPIUsersForECEnabledWebsites();
        if (!$activeApiUsers) {
            return;
        }

        // Expire pending sends older than 24 hours
        $this->smsMessageQueueManager->expirePendingSends();

        // Update status of in-progress SMS messages
        foreach ($activeApiUsers as $apiUser) {
            $client = $this->smsClientFactory->create(
                $apiUser['firstWebsiteId']
            );
            if (!$client) {
                continue;
            }

            $this->senderProgressHandlerFactory->create(['data' => ['client' => $client]])
                ->updateSendsInProgress($apiUser['stores']);
        }
    }

    /**
     * Retrieve a list of active API users with the websites they are associated with.
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getAPIUsersForECEnabledWebsites()
    {
        $websites = $this->storeManager->getWebsites(true);
        $apiUsers = [];
        /** @var \Magento\Store\Model\Website $website */
        foreach ($websites as $website) {
            $websiteId = $website->getId();
            if ($this->helper->isEnabled($websiteId)) {
                $apiUser = $this->helper->getApiUsername($websiteId);
                foreach ($website->getStoreIds() as $storeId) {
                    if ($this->moduleConfig->isTransactionalSmsEnabled($storeId)) {
                        if (!isset($apiUsers[$apiUser]['firstWebsiteId'])) {
                            $apiUsers[$apiUser]['firstWebsiteId'] = $websiteId;
                        }
                        $apiUsers[$apiUser]['stores'][] = $storeId;
                    }
                }
            }
        }
        return $apiUsers;
    }
}
