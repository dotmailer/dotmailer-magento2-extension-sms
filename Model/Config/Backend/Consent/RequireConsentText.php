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

class RequireConsentText extends Value
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
     * Require consent text before enabling SMS marketing consent (in any context).
     *
     * @return Value
     * @throws ValidatorException
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        if ($value == "0") {
            return parent::beforeSave();
        }

        if ($this->isSmsConsentTextSetOrInherited() === false) {
            throw new ValidatorException(
                __(
                    'Please set SMS consent text before enabling SMS marketing consent.'
                )
            );
        }

        return parent::beforeSave();
    }

    /**
     * Check if consent customer text is set or inherited.
     *
     * @return bool
     */
    private function isSmsConsentTextSetOrInherited(): bool
    {
        $inheritedSmsConsentText = $this->_config->getValue(
            ConfigInterface::XML_PATH_CONSENT_SMS_MARKETING_TEXT,
            $this->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $this->getScopeCode()
        );

        /** @var \Laminas\Http\Request $request */
        $request = $this->request;
        $groups = $request->getPost('groups');
        $smsConsentText = $groups['sms']['fields']['marketing_consent_text']['value'] ?? null;

        return !empty($inheritedSmsConsentText) || !empty($smsConsentText);
    }
}
