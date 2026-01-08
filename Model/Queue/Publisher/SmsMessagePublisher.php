<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\Publisher;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Api\Data\SmsMessageTypeInterface;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Queue\Message\SmsMessageData;
use Dotdigitalgroup\Sms\Model\Queue\SmsMessage\MessageTypeFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\SerializerInterface;

class SmsMessagePublisher
{
    public const TOPIC_SMS_MESSAGE = 'ddg.sms.message';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Configuration
     */
    private $moduleConfig;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var MessageTypeFactory
     */
    private $messageTypeFactory;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param Logger $logger
     * @param Configuration $moduleConfig
     * @param SerializerInterface $serializer
     * @param MessageTypeFactory $messageTypeFactory
     * @param PublisherInterface $publisher
     */
    public function __construct(
        Logger $logger,
        Configuration $moduleConfig,
        SerializerInterface $serializer,
        MessageTypeFactory $messageTypeFactory,
        PublisherInterface $publisher
    ) {
        $this->logger = $logger;
        $this->moduleConfig = $moduleConfig;
        $this->serializer = $serializer;
        $this->messageTypeFactory = $messageTypeFactory;
        $this->publisher = $publisher;
    }

    /**
     * Publish SMS message to queue.
     *
     * @param int $typeId
     * @param array $data
     * @return bool
     */
    public function publish(int $typeId, array $data = []): bool
    {
        try {
            $message = $this->messageTypeFactory->create($typeId, $data);

            if (!$this->canQueue($typeId, $message->getStoreId())) {
                return false;
            }

            $this->queueMessage($message, $typeId);
            return true;
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Error publishing SMS message type: %d', $typeId),
                [(string) $e]
            );
            return false;
        }
    }

    /**
     * Queue message for processing.
     *
     * @param SmsMessageTypeInterface $message
     * @param int $typeId
     * @return void
     */
    private function queueMessage(SmsMessageTypeInterface $message, int $typeId): void
    {
        $messageData = new SmsMessageData();
        $messageData->setWebsiteId($message->getWebsiteId())
            ->setStoreId($message->getStoreId())
            ->setTypeId($typeId)
            ->setOrderId($message->getOrderId())
            ->setPhoneNumber($message->getPhoneNumber())
            ->setEmail($message->getEmail())
            ->setAdditionalData($this->serialiseData($message->getAdditionalData()));

        $this->publisher->publish(self::TOPIC_SMS_MESSAGE, $messageData);
    }

    /**
     * Check if message can be queued.
     *
     * @param int $typeId
     * @param int $storeId
     * @return bool
     */
    private function canQueue(int $typeId, int $storeId): bool
    {
        $enabledPath = $this->messageTypeFactory->getEnabledConfigPath($typeId);

        return $this->moduleConfig->isTransactionalSmsEnabled($storeId) &&
            $this->moduleConfig->isSmsTypeEnabled($storeId, $enabledPath);
    }

    /**
     * Serialize additional data.
     *
     * @param array $data
     * @return string
     */
    private function serialiseData(array $data): string
    {
        try {
            return $this->serializer->serialize($data);
        } catch (\InvalidArgumentException $e) {
            $this->logger->debug((string) $e);
            return '';
        }
    }
}
