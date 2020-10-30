<?php

namespace Dotdigitalgroup\Sms\Model\Message;

use Dotdigitalgroup\Sms\Api\Data\SmsOrderInterface;
use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class MessageBuilder
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var array
     */
    private $smsMessages = [];

    /**
     * MessageBuilder constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param SmsOrderInterface[] $items
     * @return array
     */
    public function makeBatch(array $items)
    {
        $batch = [];

        /** @var SmsOrderInterface $item */
        foreach ($items as $item) {
            $batch[] = [
                'to' => [
                    'phoneNumber' => $item->getPhoneNumber()
                ],
                'rules' => [
                    'sms'
                ],
                'channelOptions' => [
                    'sms' => [
                        'allowUnicode' => true,
                        'unicodeConversion' => [
                            'convertUnicodeToGsm' => false
                        ]
                    ]
                ],
                'body' => $this->getMessageText($item)
            ];
        }

        return $batch;
    }

    /**
     * @param SmsOrderInterface $item
     * @return string
     */
    private function getMessageText($item)
    {
        if (!isset($this->smsMessages[$item->getStoreId()][$item->getTypeId()])) {
            $this->setMessageText($item->getStoreId(), $item->getTypeId());
        }
        return $this->smsMessages[$item->getStoreId()][$item->getTypeId()];
    }

    /**
     * @param $storeId
     * @param $typeId
     */
    private function setMessageText($storeId, $typeId)
    {
        $this->smsMessages[$storeId][$typeId] = $this->scopeConfig->getValue(
            ConfigInterface::TRANSACTIONAL_SMS_MESSAGE_TYPES_MAP[$typeId],
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }
}
