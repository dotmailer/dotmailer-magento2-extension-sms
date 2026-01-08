<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Config\Backend\TransactionalConsent;

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
     * Prevent transactional consent text being deleted if transactional SMS consent is enabled.
     *
     * @return Value
     * @throws ValidatorException
     */
    public function beforeSave()
    {
        $value = $this->getValue();

        // Only validate if the field is being emptied
        if (!empty($value)) {
            return parent::beforeSave();
        }

        if ($this->isTransactionalConsentEnabled()) {
            throw new ValidatorException(
                __(
                    'Transactional SMS consent text must be set if transactional SMS consent is enabled.'
                )
            );
        }

        return parent::beforeSave();
    }

    /**
     * Check if transactional consent is enabled or being enabled.
     *
     * @return bool
     */
    private function isTransactionalConsentEnabled(): bool
    {
        $inheritedIsTransactionalConsentEnabled = $this->_config->isSetFlag(
            ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_CONSENT_ENABLED,
            $this->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $this->getScopeCode()
        );

        /** @var \Laminas\Http\Request $request */
        $request = $this->request;
        $groups = $request->getPost('groups');
        $isTransactionalConsentBeingEnabled = $groups['consent']['fields']['enabled']['value'] ?? false;

        if ($isTransactionalConsentBeingEnabled === "0") {
            return false;
        }

        return $inheritedIsTransactionalConsentEnabled || $isTransactionalConsentBeingEnabled;
    }
}
