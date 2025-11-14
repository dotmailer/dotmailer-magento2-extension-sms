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
    public function configureCpaasOptOutRule(int $websiteId): void
    {
        $client = $this->smsClientFactory->create($websiteId);

        $enabled = $this->getConsentEnabled($websiteId);
        if ($enabled) {
            $optOutId = $this->getConsentOptOutId($websiteId);
            $optOutGenerated = $this->getConsentOptOutGenerated($websiteId);
            if (!$optOutId) {
                $optOutRule = $this->getOrCreateOptOutRule($websiteId);
                $optOutId = $optOutRule['optOutId'];
                $optOutGenerated = $optOutRule['optOutGenerated'];
            }

            $this->saveOptOutConfig($websiteId, $optOutId, (bool)$optOutGenerated);
        } else {
            $optOutId = $this->getConsentOptOutId($websiteId);
            $optOutGenerated = $this->getConsentOptOutGenerated($websiteId);
            if ($optOutGenerated) {
                $client->deleteOptOutRule($optOutId);
            }
            $this->saveOptOutConfig($websiteId, '', false);
        }
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
        if (!$this->getConsentEnabled($websiteId)) {
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
     * Get or create opt-out rule.
     *
     * @param int $websiteId
     * @return array
     * @throws LocalizedException
     */
    private function getOrCreateOptOutRule($websiteId)
    {
        $client = $this->smsClientFactory->create($websiteId);
        $rules = $client->getOptOutRules();
        $optOutId = null;
        $optOutGenerated = 0;
        if (is_iterable($rules)) {
            foreach ($rules as $rule) {
                if ($rule->channel == 'sms' &&
                    $rule->inbound == '*' &&
                    strtolower($rule->keyword) == 'stop' &&
                    isset($rule->actionData->opt) && $rule->actionData->opt == 'out'
                ) {
                    $optOutId = $rule->id;
                    $optOutGenerated = 0;
                    break;
                }
            }
        }
        if (!$optOutId) {
            $response = $client->postOptOutRule('STOP');
            $optOutId = $response->id;
            $optOutGenerated = 1;
        }
        return ['optOutId' => $optOutId, 'optOutGenerated' => $optOutGenerated];
    }

    /**
     * Check if consent is enabled for website or websites sharing the same API user.
     *
     * @param int $websiteId
     * @return bool
     */
    private function getConsentEnabled($websiteId)
    {
        return $this->getCpaasConfig($websiteId, ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_CONSENT_ENABLED);
    }

    /**
     * Get consent opt-out ID for website or websites sharing the same API user.
     *
     * @param int $websiteId
     * @return string|null
     */
    private function getConsentOptOutId($websiteId)
    {
        return $this->getCpaasConfig($websiteId, ConfigInterface::XML_PATH_CPAAS_OPTOUT_ID);
    }

    /**
     * Check if opt-out was generated for website or websites sharing the same API user.
     *
     * @param int $websiteId
     * @return bool
     */
    private function getConsentOptOutGenerated($websiteId)
    {
        return $this->getCpaasConfig($websiteId, ConfigInterface::XML_PATH_CPAAS_OPTOUT_GENERATED);
    }

    /**
     * Get CPaaS configuration value for website or websites sharing the same API user.
     *
     * @param int $websiteId
     * @param string $configKey
     * @return mixed
     */
    private function getCpaasConfig($websiteId, $configKey)
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
        }
        return false;
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
     * Save opt-out configuration for website and websites sharing the same API user.
     *
     * @param int $websiteId
     * @param string $optOutId
     * @param bool $generated
     * @return void
     */
    private function saveOptOutConfig(int $websiteId, string $optOutId, bool $generated): void
    {
        $websiteIds = $this->getWebsitesSharingApiUser($websiteId);
        foreach ($websiteIds as $websiteId) {
            $this->configWriter->save(
                ConfigInterface::XML_PATH_CPAAS_OPTOUT_ID,
                $optOutId,
                ScopeInterface::SCOPE_WEBSITES,
                $websiteId
            );
            $this->configWriter->save(
                ConfigInterface::XML_PATH_CPAAS_OPTOUT_GENERATED,
                (int)$generated,
                ScopeInterface::SCOPE_WEBSITES,
                $websiteId
            );
        }
    }
}
