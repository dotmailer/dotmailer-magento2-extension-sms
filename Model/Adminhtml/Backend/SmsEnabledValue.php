<?php

namespace Dotdigitalgroup\Sms\Model\Adminhtml\Backend;

use Dotdigitalgroup\Sms\Model\Account;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Magento\Framework\App\Config\Value;

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
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param Account $account
     * @param Configuration $smsConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        Account $account,
        Configuration $smsConfig,
        array $data = []
    ) {
        $this->account = $account;
        $this->smsConfig = $smsConfig;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return SmsEnabledValue|void
     */
    protected function _afterLoad()
    {
        if ($this->getValue() === '1' && !$this->account->canSendSmsInCurrentScope()) {
            $this->smsConfig->forceSwitchOff();
            $this->setValue(0);
        }
    }
}
