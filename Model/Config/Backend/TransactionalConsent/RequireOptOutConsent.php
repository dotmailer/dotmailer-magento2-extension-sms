<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Config\Backend\TransactionalConsent;

use Dotdigitalgroup\Email\Helper\Data as Helper;
use Dotdigitalgroup\Sms\Model\Apiconnector\SmsClientFactory;
use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class RequireOptOutConsent extends Value
{
    private const XML_PATH_CPAAS_OPTOUT_ID = 'transactional_sms/consent/cpaas_optout_id';
    private const XML_PATH_CPAAS_OPTOUT_GENERATED = 'transactional_sms/consent/cpaas_optout_generated';

    /**
     * @var RequestInterface
     */
    private $request;

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
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param RequestInterface $request
     * @param WriterInterface $configWriter
     * @param Helper $helper
     * @param SmsClientFactory $smsClientFactory
     * @param StoreManagerInterface $storeManager
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        RequestInterface $request,
        WriterInterface $configWriter,
        Helper $helper,
        SmsClientFactory $smsClientFactory,
        StoreManagerInterface $storeManager,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->request = $request;
        $this->configWriter = $configWriter;
        $this->helper = $helper;
        $this->smsClientFactory = $smsClientFactory;
        $this->storeManager = $storeManager;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Require consent text before enabling SMS marketing consent (in any context).
     *
     * @return Value
     * @throws ValidatorException|LocalizedException
     */
    public function beforeSave(): Value
    {
        $value = $this->getValue();
        if ($value == "1" && !$this->isTransactionalSmsConsentTextSetOrInherited()) {
            throw new ValidatorException(
                __(
                    'Please set transactional SMS consent text before enabling transactional SMS consent.'
                )
            );
        }

        $this->configureCpaasOptOutRule();

        return parent::beforeSave();
    }

    /**
     * Check if transactional sms consent text is set or inherited.
     *
     * @return bool
     */
    private function isTransactionalSmsConsentTextSetOrInherited(): bool
    {
        $inheritedTransactionalConsentText = $this->_config->getValue(
            ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_CONSENT_TEXT,
            $this->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $this->getScopeCode()
        );

        /** @var \Laminas\Http\Request $request */
        $request = $this->request;
        $groups = $request->getPost('groups');
        $transactionalConsentText = $groups['consent']['fields']['text']['value'] ?? null;

        return !empty($inheritedTransactionalConsentText) || !empty($transactionalConsentText);
    }

    /**
     * Create CPaaS opt-out rule via API and stores the rule ID.
     *
     * Marks the rule as generated if new rule is created to prevent deleting pre-configured cpaas rules.
     *
     * @return void
     * @throws LocalizedException If API call fails or configuration cannot be saved
     */
    private function configureCpaasOptOutRule(): void
    {
        $value = $this->getValue();
        $websiteId = (int) $this->helper->getWebsiteForSelectedScopeInAdmin()->getId();
        $client = $this->smsClientFactory->create($websiteId);

        $optOutId = $newOptOutId = $this->_config->getValue(
            self::XML_PATH_CPAAS_OPTOUT_ID,
            ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        );

        $optOutGenerated = $newOptOutGenerated = $this->_config->getValue(
            self::XML_PATH_CPAAS_OPTOUT_GENERATED,
            ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        );

        if ($value == "1" && !$optOutId) {
            $rules = $client->getCpaasOptOutRules();
            foreach ($rules as $rule) {
                if ($rule->channel == 'sms' &&
                    $rule->inbound == '*' &&
                    strtolower($rule->keyword) == 'stop' &&
                    isset($rule->actionData->opt) && $rule->actionData->opt == 'out'
                ) {
                    $newOptOutId = $rule->id;
                    $newOptOutGenerated = 0;
                    break;
                }
            }
            if (!$newOptOutId) {
                $response = $client->postCpaasOptOutRule('STOP');
                $newOptOutId = $response->id;
                $newOptOutGenerated = 1;
            }
        }

        if ($value == "0" && $optOutId) {
            if ($optOutGenerated && $this->isOptOutRuleOnlyUsedByCurrentWebsite($optOutId, $websiteId)) {
                $client->deleteCpaasOptOutRule($optOutId);
            }
            $newOptOutGenerated = 0;
            $newOptOutId = '';
        }

        if ($optOutId !== $newOptOutId) {
            $this->configWriter->save(
                self::XML_PATH_CPAAS_OPTOUT_ID,
                $newOptOutId,
                ScopeInterface::SCOPE_WEBSITES,
                $websiteId
            );
        }

        if ($optOutGenerated !== $newOptOutGenerated) {
            $this->configWriter->save(
                self::XML_PATH_CPAAS_OPTOUT_GENERATED,
                $newOptOutGenerated,
                ScopeInterface::SCOPE_WEBSITES,
                $websiteId
            );
        }
    }

    /**
     * Check if the opt-out rule ID is only used by the current website.
     *
     * @param string $optOutId The opt-out rule ID to check
     * @param int $currentWebsiteId The current website ID to exclude from the check
     * @return bool True if the opt-out rule is only used by the current website, false otherwise
     */
    private function isOptOutRuleOnlyUsedByCurrentWebsite(string $optOutId, int $currentWebsiteId): bool
    {
        $websites = $this->storeManager->getWebsites(true);

        foreach ($websites as $website) {
            if ($website->getId() == $currentWebsiteId) {
                continue;
            }

            $websiteOptOutId = $this->_config->getValue(
                self::XML_PATH_CPAAS_OPTOUT_ID,
                ScopeInterface::SCOPE_WEBSITES,
                $website->getId()
            );

            if ($websiteOptOutId === $optOutId) {
                return false;
            }
        }

        return true;
    }
}
