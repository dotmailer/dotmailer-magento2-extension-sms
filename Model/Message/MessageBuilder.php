<?php

namespace Dotdigitalgroup\Sms\Model\Message;

use Dotdigitalgroup\Sms\Api\Data\SmsMessageInterface;
use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Dotdigitalgroup\Sms\Model\Config\Source\FromName;
use Dotdigitalgroup\Sms\Model\Message\Text\Compiler;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class MessageBuilder
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Compiler
     */
    private $messageCompiler;

    /**
     * @var array
     */
    private $smsTemplates = [];

    /**
     * MessageBuilder constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Compiler $messageCompiler
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Compiler $messageCompiler
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->messageCompiler = $messageCompiler;
    }

    /**
     * Build message text.
     *
     * @param SmsMessageInterface $item
     * @param bool $requireOptIn
     * @return array
     */
    public function buildMessage(SmsMessageInterface $item, bool $requireOptIn = false)
    {
        $message = [
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
            'body' => $this->getCompiledMessageText($item)
        ];

        $originator = $this->getOriginator($item->getStoreId());

        if ($originator) {
            $message['channelOptions']['sms']['from'] = $originator;
        }

        if ($requireOptIn) {
            $message['requireOptIn'] = true;
            $optOutText = $this->getOptOutText($item->getStoreId());
            if (!empty($optOutText)) {
                $message['body'] .= "\n\n" . $optOutText;
            }
        }

        return $message;
    }

    /**
     * Batch message text.
     *
     * @param SmsMessageInterface[] $items
     * @return array
     */
    public function makeBatch(array $items)
    {
        $batch = [];

        foreach ($items as $item) {
            $batch[] = $this->buildMessage($item);
        }

        return $batch;
    }

    /**
     * Get compiled message text.
     *
     * @param SmsMessageInterface $item
     * @return string
     */
    private function getCompiledMessageText($item)
    {
        if (!isset($this->smsTemplates[$item->getStoreId()][$item->getTypeId()])) {
            $this->setRawMessageText($item->getStoreId(), $item->getTypeId());
        }
        return $this->messageCompiler->compile(
            $this->smsTemplates[$item->getStoreId()][$item->getTypeId()],
            $item
        );
    }

    /**
     * Get opt-out text.
     *
     * @param string|int $storeId
     * @return string|null
     */
    private function getOptOutText($storeId)
    {
        return $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_OPTOUT_TEXT,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * Set raw message text.
     *
     * @param string|int $storeId
     * @param string|int $typeId
     */
    private function setRawMessageText($storeId, $typeId)
    {
        $this->smsTemplates[$storeId][$typeId] = $this->scopeConfig->getValue(
            ConfigInterface::TRANSACTIONAL_SMS_MESSAGE_TYPES_MAP[$typeId]['message_path'],
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * Get originator for SMS send.
     *
     * This can be a from name, or a dedicated number, or a shortcode.
     * Or it can be null (if shared pool number was selected).
     *
     * @param string|int $storeId
     * @return string|null
     */
    private function getOriginator($storeId)
    {
        $originator = $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_DEFAULT_FROM_NAME,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );

        if ($originator === FromName::SHARED_POOL_NUMBER) {
            return null;
        }

        if ($originator === FromName::ALPHANUMERIC_NUMBER) {
            return $this->scopeConfig->getValue(
                ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_ALPHANUMERIC_FROM_NAME,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
        }

        return $originator;
    }
}
