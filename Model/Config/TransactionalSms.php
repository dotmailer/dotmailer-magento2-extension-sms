<?php

namespace Dotdigitalgroup\Sms\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class TransactionalSms
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
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
     * @param $storeId
     * @param $smsPath
     * @return mixed
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
}
