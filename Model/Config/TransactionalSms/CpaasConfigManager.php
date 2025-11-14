<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Config\TransactionalSms;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Task\TaskRunInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Manages CPaaS configuration tasks for enabled websites.
 */
class CpaasConfigManager implements TaskRunInterface
{
    /**
     * @var CpaasConfigService
     */
    private CpaasConfigService $cpaasConfigService;

    /**
     * @var Data
     */
    private Data $helper;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @param CpaasConfigService $cpaasConfigService
     * @param Data $helper
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     */
    public function __construct(
        CpaasConfigService $cpaasConfigService,
        Data $helper,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Logger $logger
    ) {
        $this->cpaasConfigService = $cpaasConfigService;
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Run CPaaS configuration for all enabled websites.
     *
     * @return void
     */
    public function run(): void
    {
        $apiUsers = $this->cpaasConfigService->getAPIUsersForEnabledWebsites();

        foreach ($apiUsers as $apiUser) {
            $websiteId = $apiUser['websiteId'];

            try {
                $this->cpaasConfigService->saveCpaasProfilesStatus($websiteId, 'pending');
                $this->cpaasConfigService->configureCpaasOptOutRule($websiteId);
                $this->cpaasConfigService->configureCpaasProfileDefaults($websiteId);
                $this->cpaasConfigService->saveCpaasProfilesStatus($websiteId, 'configured');

                $this->logger->info(
                    sprintf('CPaaS configuration completed for website IDs: %s', implode(',', $apiUser['websiteIds']))
                );
            } catch (\Exception $e) {
                $this->logger->error(
                    sprintf(
                        'Error configuring CPaaS for website IDs %s: %s',
                        implode(',', $apiUser['websiteIds']),
                        $e->getMessage()
                    )
                );

                $this->cpaasConfigService->saveCpaasProfilesStatus($websiteId, 'error');
            }
        }
    }
}
