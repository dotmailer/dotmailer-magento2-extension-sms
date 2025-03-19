<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Config\Backend\Consent;

use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class PreventConsentTextDeletion extends Value
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param RequestInterface $request
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
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->request = $request;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Prevent consent text being deleted if SMS marketing consent is enabled.
     *
     * @return Value
     * @throws ValidatorException
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        if (!empty($value)) {
            return parent::beforeSave();
        }

        if ($this->isConsentEnabledOrBeingEnabledAnywhere()) {
            throw new ValidatorException(
                __(
                    'SMS marketing consent text must be set if SMS marketing consent is enabled in any context.'
                )
            );
        }

        return parent::beforeSave();
    }

    /**
     * Check if consent is enabled or inherited in any context.
     *
     * @return bool
     */
    private function isConsentEnabledOrBeingEnabledAnywhere(): bool
    {
        $inheritedIsConsentEnabledRegistration = $this->_config->isSetFlag(
            ConfigInterface::XML_PATH_CONSENT_SMS_REGISTRATION_ENABLED,
            $this->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $this->getScopeCode()
        );
        $inheritedIsConsentEnabledCheckout = $this->_config->isSetFlag(
            ConfigInterface::XML_PATH_CONSENT_SMS_CHECKOUT_ENABLED,
            $this->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $this->getScopeCode()
        );
        $inheritedIsConsentEnabledAccount = $this->_config->isSetFlag(
            ConfigInterface::XML_PATH_CONSENT_SMS_ACCOUNT_ENABLED,
            $this->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $this->getScopeCode()
        );

        $isConsentInheritedSomehere = $inheritedIsConsentEnabledRegistration ||
            $inheritedIsConsentEnabledCheckout ||
            $inheritedIsConsentEnabledAccount;

        /** @var \Laminas\Http\Request $request */
        $request = $this->request;
        $groups = $request->getPost('groups');
        $isConsentBeingEnabledRegistration = $groups['sms']['fields']['registration_enabled']['value'] ?? false;
        $isConsentBeingEnabledCheckout = $groups['sms']['fields']['checkout_enabled']['value'] ?? false;
        $isConsentBeingEnabledAccount = $groups['sms']['fields']['account_enabled']['value'] ?? false;

        $isConsentBeingEnabled = $isConsentBeingEnabledAccount ||
            $isConsentBeingEnabledCheckout ||
            $isConsentBeingEnabledRegistration;

        return $isConsentInheritedSomehere || $isConsentBeingEnabled;
    }
}
