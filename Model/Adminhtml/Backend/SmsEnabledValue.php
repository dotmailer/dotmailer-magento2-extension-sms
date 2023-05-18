<?php

namespace Dotdigitalgroup\Sms\Model\Adminhtml\Backend;

use Dotdigitalgroup\Sms\Model\Account;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class SmsEnabledValue extends Value
{
    /**
     * @var Account
     */
    private $account;

    /**
     * @var Configuration
     */
    private $smsConfig;

    /**
     * Serialized constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param Account $account
     * @param Configuration $smsConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        Account $account,
        Configuration $smsConfig,
        array $data = []
    ) {
        $this->account = $account;
        $this->smsConfig = $smsConfig;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * After load event handler.
     *
     * @return SmsEnabledValue|void
     * @throws NoSuchEntityException
     */
    protected function _afterLoad()
    {
        if ($this->getValue() === '1' && !$this->account->canSendSmsInCurrentScope()) {
            $this->smsConfig->forceSwitchOff();
            $this->setValue(0);
        }
    }
}
