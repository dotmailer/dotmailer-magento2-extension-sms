<?php

namespace Dotdigitalgroup\Sms\Model\Config;

use Magento\Directory\Model\AllowedCountries;
use Magento\Config\Model\Config\Backend\Admin\Custom;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\StoreWebsiteRelationInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Dotdigitalgroup\Email\Helper\Config;

class Configuration
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
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    /**
     * @var StoreWebsiteRelationInterface
     */
    private $storeWebsiteRelation;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Transactional SMS constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param RequestInterface $request
     * @param ReinitableConfigInterface $reinitableConfig
     * @param StoreWebsiteRelationInterface $storeWebsiteRelation
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        RequestInterface $request,
        ReinitableConfigInterface $reinitableConfig,
        StoreWebsiteRelationInterface $storeWebsiteRelation,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->request = $request;
        $this->reinitableConfig = $reinitableConfig;
        $this->storeWebsiteRelation = $storeWebsiteRelation;
        $this->storeManager = $storeManager;
    }

    /**
     * Check if Transactional SMS > Enabled is set to Yes.
     *
     * @param int $storeId
     * @return bool
     */
    public function isSmsEnabled($storeId)
    {
        return $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_ENABLED,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * Is phone number validation enabled.
     *
     * @param string|int $storeId
     * @return bool
     */
    public function isPhoneNumberValidationEnabled($storeId)
    {
        return $this->scopeConfig->isSetFlag(
            ConfigInterface::XML_PATH_SMS_PHONE_NUMBER_VALIDATION,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * SMS enabled at website level.
     *
     * @param string|int $websiteId
     * @return bool
     */
    private function isSmsEnabledAtWebsiteLevel($websiteId)
    {
        return $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_ENABLED,
            ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        );
    }

    /**
     * SMS type enabled.
     *
     * @param string|int $storeId
     * @param string $smsPath
     * @return bool
     */
    public function isSmsTypeEnabled($storeId, $smsPath)
    {
        return $this->scopeConfig->getValue(
            $smsPath,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * Is SMS consent capture enabled.
     *
     * @param string|int $storeId
     * @return bool
     */
    public function isSmsConsentEnabled($storeId)
    {
        return $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_CONSENT_SMS_ENABLED,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * Get SMS marketing consent text.
     *
     * @param string|int $storeId
     * @return string
     */
    public function getSmsSignUpText($storeId)
    {
        return $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_CONSENT_SMS_SIGNUP_TEXT,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * Get SMS marketing consent text.
     *
     * @param string|int $storeId
     * @return string
     */
    public function getSmsMarketingConsentText($storeId): string
    {
        return (string) $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_CONSENT_SMS_MARKETING_TEXT,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * Switch off at store level.
     *
     * @param string|int $storeId
     */
    private function switchOffAtStoreLevel($storeId)
    {
        $this->configWriter->save(
            ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_ENABLED,
            0,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * Switch off at website level.
     *
     * @param string|int $websiteId
     */
    private function switchOffAtWebsiteLevel($websiteId)
    {
        $this->configWriter->save(
            ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_ENABLED,
            0,
            ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        );
    }

    /**
     * Switch off at default level.
     */
    private function switchOffAtDefaultLevel()
    {
        $this->configWriter->save(
            ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_ENABLED,
            0
        );
    }

    /**
     * Switch off all child stores.
     *
     * @param string|int $websiteId
     */
    private function switchOffForAllChildStores($websiteId)
    {
        $childStores = $this->storeWebsiteRelation->getStoreByWebsiteId($websiteId);
        foreach ($childStores as $storeId) {
            if ($this->isSmsEnabled($storeId)) {
                $this->switchOffAtStoreLevel($storeId);
            }
        }
    }

    /**
     * Can be overridden via config.xml
     *
     * @return string
     */
    public function getBatchSize()
    {
        return $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_BATCH_SIZE
        );
    }

    /**
     * Get preferred country.
     *
     * @param string|int $websiteId
     * @return string
     */
    public function getPreferredCountry($websiteId)
    {
        return $this->scopeConfig->getValue(
            Custom::XML_PATH_GENERAL_COUNTRY_DEFAULT,
            ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        );
    }

    /**
     * Get allowed countries.
     *
     * @param string|int $websiteId
     * @return string
     */
    public function getAllowedCountries($websiteId)
    {
        return $this->scopeConfig->getValue(
            AllowedCountries::ALLOWED_COUNTRIES_PATH,
            ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        );
    }

    /**
     * Prepare telephone field config according to the Magento default config.
     *
     * @param string $addressType
     * @param string $method
     * @return array
     */
    public function telephoneFieldConfig($addressType, $method = '')
    {
        return  [
            'component' => 'Magento_Ui/js/form/element/abstract',
            'config' => [
                'customScope' => $addressType . $method,
                'customEntry' => null,
                'template' => 'ui/form/field',
                'elementTmpl' => 'Dotdigitalgroup_Sms/form/element/telephone',
                'tooltip' => [
                    'description' => 'For SMS order notifications.',
                    'tooltipTpl' => 'ui/form/element/helper/tooltip'
                ],
            ],
            'dataScope' => $addressType . $method . '.telephone',
            'label' => __('Phone Number'),
            'provider' => 'checkoutProvider',
            'sortOrder' => 120,
            'validation' => [
                "required-entry" => true,
                "max_text_length" => 255,
                "min_text_length" => 1,
                'validate-phone-number' => true
            ],
            'options' => [],
            'filterBy' => null,
            'customEntry' => null,
            'visible' => true,
            'focused' => false,
        ];
    }

    /**
     * Prepare phone resubmission fieldset.
     *
     * @return array
     */
    public function getResubmissionForm(): array
    {
         return [
             'component' => 'Dotdigitalgroup_Sms/js/view/telephoneResubmission',
             'provider' => 'checkoutProvider',
             'config' => [
                 'template' => 'Dotdigitalgroup_Sms/telephone-resubmission'
             ],
             'children' => [
                 'telephone-resubmission-fieldset' => [
                     'component' => 'uiComponent',
                     'displayArea' => 'telephone-resubmission-fields',
                     'children' => [
                         'telephone' => $this->telephoneFieldConfig('telephone', 'Resubmission')
                     ]
                 ]
             ]
         ];
    }

    /**
     * Get data scope prefix.
     *
     * @param string $addressType
     * @param string $method
     * @return string
     */
    public function getDataScopePrefix($addressType, $method = '')
    {
        return $addressType . $method;
    }

    /**
     * Check if SMS sync is enabled.
     *
     * @param int $websiteId
     * @return bool
     */
    public function isSmsSyncEnabled($websiteId):bool
    {
        return (bool) $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_CONNECTOR_SMS_SUBSCRIBER_SYNC_ENABLED,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Get the limit for the sync.
     *
     * @param int $websiteId
     * @return int
     */
    public function getLimit($websiteId): int
    {
        return (int) $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_SYNC_LIMIT,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Get address book list ID.
     *
     * @param int $websiteId
     * @return int
     */
    public function getListId($websiteId): int
    {
        return (int) $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_CONNECTOR_SMS_SUBSCRIBER_ADDRESS_BOOK_ID,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }
}
