<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Config\TransactionalSms;

use Dotdigitalgroup\Email\Helper\Data as Helper;
use Dotdigitalgroup\Sms\Model\Apiconnector\SmsClientFactory;
use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsMessageFactory;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class CpaasConfigService
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var SmsClientFactory
     */
    private $smsClientFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var SmsMessageFactory
     */
    private $smsMessageResourceFactory;

    /**
     * @var ReinitableConfigInterface
     */
    private ReinitableConfigInterface $reinitableConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param Helper $helper
     * @param SmsClientFactory $smsClientFactory
     * @param StoreManagerInterface $storeManager
     * @param SmsMessageFactory $smsMessageResourceFactory
     * @param ReinitableConfigInterface $reinitableConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        Helper $helper,
        SmsClientFactory $smsClientFactory,
        StoreManagerInterface $storeManager,
        SmsMessageFactory $smsMessageResourceFactory,
        ReinitableConfigInterface $reinitableConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->helper = $helper;
        $this->smsClientFactory = $smsClientFactory;
        $this->storeManager = $storeManager;
        $this->smsMessageResourceFactory = $smsMessageResourceFactory;
        $this->reinitableConfig = $reinitableConfig;
    }

    /**
     * Retrieve a list of active API users with the websites they are associated with.
     *
     * @return array
     */
    public function getAPIUsersForEnabledWebsites()
    {
        $websites = $this->storeManager->getWebsites(true);
        $apiUsers = [];
        /** @var \Magento\Store\Model\Website $website */
        foreach ($websites as $website) {
            $websiteId = $website->getId();
            if ($this->helper->isEnabled($websiteId)) {
                $apiUser = $this->helper->getApiUsername($websiteId);
                $apiUsers[$apiUser]['websiteIds'][] = (int) $websiteId;
                if (!isset($apiUsers[$apiUser]['websiteId'])) {
                    $apiUsers[$apiUser]['websiteId'] = (int) $websiteId;
                }
            }
        }
        return $apiUsers;
    }

    /**
     * Configure CPaaS opt-out rule.
     *
     * @param int $websiteId
     * @return void
     * @throws LocalizedException
     */
    public function configureCpaasInboundRules(int $websiteId): void
    {
        $client = $this->smsClientFactory->create($websiteId);
        $enabled = $this->getCpaasConfig($websiteId, ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_CONSENT_ENABLED, true);
        $optOutId = $this->getCpaasConfig($websiteId, ConfigInterface::XML_PATH_CPAAS_OPTOUT_ID);
        $optOutGenerated = $this->getCpaasConfig($websiteId, ConfigInterface::XML_PATH_CPAAS_OPTOUT_GENERATED);
        $optInId = $this->getCpaasConfig($websiteId, ConfigInterface::XML_PATH_CPAAS_OPTIN_ID);
        $optInGenerated = $this->getCpaasConfig($websiteId, ConfigInterface::XML_PATH_CPAAS_OPTIN_GENERATED);

        if ($enabled) {
            if (!$optOutId) {
                $optOutRule = $this->getOrCreateInboundRule($websiteId, 'STOP', 'out');
                $optOutId = $optOutRule['ruleId'];
                $optOutGenerated = $optOutRule['ruleGenerated'];
            }
            if (!$optInId) {
                $optInRule = $this->getOrCreateInboundRule($websiteId, 'START', 'in');
                $optInId = $optInRule['ruleId'];
                $optInGenerated = $optInRule['ruleGenerated'];
            }
        } else {
            if ($optOutId && $optOutGenerated) {
                $client->deleteInboundRule($optOutId);
            }
            if ($optInId && $optInGenerated) {
                $client->deleteInboundRule($optInId);
            }
            $optOutId = '';
            $optOutGenerated = 0;
            $optInId = '';
            $optInGenerated = 0;
        }

        $this->saveCpaasConfig($websiteId, ConfigInterface::XML_PATH_CPAAS_OPTOUT_ID, $optOutId);
        $this->saveCpaasConfig($websiteId, ConfigInterface::XML_PATH_CPAAS_OPTOUT_GENERATED, $optOutGenerated);
        $this->saveCpaasConfig($websiteId, ConfigInterface::XML_PATH_CPAAS_OPTIN_ID, $optInId);
        $this->saveCpaasConfig($websiteId, ConfigInterface::XML_PATH_CPAAS_OPTIN_GENERATED, $optInGenerated);
    }

    /**
     * Configure CPaaS profile defaults.
     *
     * @param int $websiteId
     * @return void
     * @throws LocalizedException
     */
    public function configureCpaasProfileDefaults(int $websiteId): void
    {
        $enabled = $this->getCpaasConfig($websiteId, ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_CONSENT_ENABLED);
        if (!$enabled) {
            return;
        }

        $client = $this->smsClientFactory->create($websiteId);

        $client->updateProfilesOptInDefaults(['sms' => true]);

        $uniquePhoneNumbers = $this->smsMessageResourceFactory->create()->getUniquePhoneNumbers();

        $profiles = $client->getProfiles('?phoneNumberCountryCode=~US');
        if (is_iterable($profiles)) {
            foreach ($profiles as $profile) {
                if (isset($profile->_optIn) && isset($profile->_optIn->sms)) {
                    continue;
                }
                if (!isset($profile->phoneNumber) || !in_array($profile->phoneNumber, $uniquePhoneNumbers)) {
                    continue;
                }

                $client->updateProfileOptIn($profile->id, ['channels' => ['sms']]);
            }
        }
    }

    /**
     * Save CPaaS profiles status for website and websites sharing the same API user.
     *
     * @param int $websiteId
     * @param string $status
     * @return void
     */
    public function saveCpaasProfilesStatus(int $websiteId, string $status): void
    {
        $websiteIds = $this->getWebsitesSharingApiUser($websiteId);
        foreach ($websiteIds as $websiteId) {
            $this->configWriter->save(
                ConfigInterface::XML_PATH_CPAAS_PROFILES_STATUS,
                $status,
                ScopeInterface::SCOPE_WEBSITES,
                $websiteId
            );
        }
        //Clear config cache
        $this->reinitableConfig->reinit();
    }

    /**
     * Get or create CPaaS inbound rule.
     *
     * @param int $websiteId
     * @param string $keyword
     * @param string $action
     *
     * @return array
     * @throws LocalizedException
     */
    private function getOrCreateInboundRule($websiteId, $keyword, $action)
    {
        $client = $this->smsClientFactory->create($websiteId);
        $rules = $client->getInboundRules();
        $ruleId = null;
        $ruleGenerated = 0;
        if (is_iterable($rules)) {
            foreach ($rules as $rule) {
                if ($rule->channel == 'sms' &&
                    $rule->inbound == '*' &&
                    strtolower($rule->keyword) == strtolower($keyword) &&
                    isset($rule->actionData->opt) && $rule->actionData->opt == $action
                ) {
                    $ruleId = $rule->id;
                    $ruleGenerated = 0;
                    break;
                }
            }
        }
        if (!$ruleId) {
            $response = $client->postInboundRule($keyword, $action);
            $ruleId = $response->id;
            $ruleGenerated = 1;
        }
        return ['ruleId' => $ruleId, 'ruleGenerated' => $ruleGenerated];
    }

    /**
     * Get websites sharing the same API user.
     *
     * @param int $websiteId
     * @return array
     */
    private function getWebsitesSharingApiUser($websiteId)
    {
        $websiteIds = [];
        $apiUsers = $this->getAPIUsersForEnabledWebsites();
        foreach ($apiUsers as $apiUser) {
            if (in_array($websiteId, $apiUser['websiteIds'])) {
                $websiteIds = $apiUser['websiteIds'];
            }
        }
        return $websiteIds;
    }

    /**
     * Get CPaaS configuration value for website or websites sharing the same API user.
     *
     * @param int $websiteId
     * @param string $configKey
     * @param bool $checkStoreScope
     *
     * @return mixed
     * @throws LocalizedException
     */
    private function getCpaasConfig($websiteId, $configKey, $checkStoreScope = false)
    {
        $websiteIds = $this->getWebsitesSharingApiUser($websiteId);

        foreach ($websiteIds as $websiteId) {
            $conf = $this->scopeConfig->getValue(
                $configKey,
                ScopeInterface::SCOPE_WEBSITES,
                $websiteId
            );
            if ($conf) {
                return $conf;
            }

            if ($checkStoreScope) {
                /** @var \Magento\Store\Model\Website $website */
                $website = $this->storeManager->getWebsite($websiteId);
                foreach ($website->getStores() as $store) {
                    $storeConf = $this->scopeConfig->getValue(
                        $configKey,
                        ScopeInterface::SCOPE_STORES,
                        $store->getId()
                    );

                    if ($storeConf) {
                        return $storeConf;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Saves CPaaS configuration for website and websites sharing the same API user.
     *
     * @param int $websiteId
     * @param string $configKey
     * @param mixed $configValue
     * @return void
     */
    private function saveCpaasConfig(int $websiteId, string $configKey, $configValue): void
    {
        $websiteIds = $this->getWebsitesSharingApiUser($websiteId);
        foreach ($websiteIds as $websiteId) {
            $this->configWriter->save(
                $configKey,
                $configValue,
                ScopeInterface::SCOPE_WEBSITES,
                $websiteId
            );
        }
    }
}
