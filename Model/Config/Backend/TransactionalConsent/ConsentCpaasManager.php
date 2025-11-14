<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Config\Backend\TransactionalConsent;

use Dotdigitalgroup\Email\Helper\Data as Helper;
use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Dotdigitalgroup\Sms\Model\Queue\Publisher\CpaasConfigMessagePublisher;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

/**
 * Backend model for transactional SMS consent configuration.
 *
 * Validates consent text presence and publishes CPaaS configuration messages on value changes.
 */
class ConsentCpaasManager extends Value
{
    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var Helper
     */
    private Helper $helper;

    /**
     * @var CpaasConfigMessagePublisher
     */
    private CpaasConfigMessagePublisher $cpaasConfigMessagePublisher;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param RequestInterface $request
     * @param Helper $helper
     * @param CpaasConfigMessagePublisher $cpaasConfigMessagePublisher
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
        Helper $helper,
        CpaasConfigMessagePublisher $cpaasConfigMessagePublisher,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->request = $request;
        $this->helper = $helper;
        $this->cpaasConfigMessagePublisher = $cpaasConfigMessagePublisher;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Require consent text before enabling Transactional SMS consent (in any context).
     *
     * Publishes message to queue for CPaaS configuration if value has changed.
     *
     * @return Value
     * @throws ValidatorException
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

        if ($value != $this->getOldValue()) {
            $websiteId = (int) $this->helper->getWebsiteForSelectedScopeInAdmin()->getId();
            $this->cpaasConfigMessagePublisher->publish($websiteId);
        }

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

        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $this->request;
        $groups = $request->getPost('groups');
        $transactionalConsentText = $groups['consent']['fields']['text']['value'] ?? null;

        return !empty($inheritedTransactionalConsentText) || !empty($transactionalConsentText);
    }
}
