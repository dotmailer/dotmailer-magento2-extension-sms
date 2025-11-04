<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\SmsMessage;

use Dotdigitalgroup\Sms\Api\Data\SmsMessageTypeInterface;
use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Magento\Framework\ObjectManagerInterface;

class MessageTypeFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create message type instance.
     *
     * @param int $typeId
     * @param array $args
     * @return SmsMessageTypeInterface
     * @throws \InvalidArgumentException
     */
    public function create(int $typeId, array $args = []): SmsMessageTypeInterface
    {
        if (!isset(ConfigInterface::TRANSACTIONAL_SMS_MESSAGE_TYPES_MAP[$typeId])) {
            throw new \InvalidArgumentException(
                sprintf('Unknown message type ID: %s', $typeId)
            );
        }

        $config = ConfigInterface::TRANSACTIONAL_SMS_MESSAGE_TYPES_MAP[$typeId];

        return $this->objectManager->create($config['class'], $args);
    }

    /**
     * Get enabled config path for message type.
     *
     * @param int $typeId
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getEnabledConfigPath(int $typeId): string
    {
        if (!isset(ConfigInterface::TRANSACTIONAL_SMS_MESSAGE_TYPES_MAP[$typeId])) {
            throw new \InvalidArgumentException(
                sprintf('Unknown message type ID: %s', $typeId)
            );
        }

        return ConfigInterface::TRANSACTIONAL_SMS_MESSAGE_TYPES_MAP[$typeId]['enabled_path'];
    }

    /**
     * Get message config path for message type.
     *
     * @param int $typeId
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getMessageConfigPath(int $typeId): string
    {
        if (!isset(ConfigInterface::TRANSACTIONAL_SMS_MESSAGE_TYPES_MAP[$typeId])) {
            throw new \InvalidArgumentException(
                sprintf('Unknown message type ID: %s', $typeId)
            );
        }

        return ConfigInterface::TRANSACTIONAL_SMS_MESSAGE_TYPES_MAP[$typeId]['message_path'];
    }
}
