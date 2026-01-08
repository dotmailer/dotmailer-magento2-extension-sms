<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\Item;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Api\Data\SmsMessageInterfaceFactory;
use Dotdigitalgroup\Sms\Api\SmsMessageRepositoryInterface;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use InvalidArgumentException;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class TransactionalMessageEnqueuer
 *
 * @deprecated This class is deprecated and will be removed in a future version.
 * The queue system now uses a publisher and consumer.
 * @see \Dotdigitalgroup\Sms\Model\Queue\Publisher\SmsMessagePublisher
 */

class TransactionalMessageEnqueuer
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SmsMessageInterfaceFactory
     */
    private $smsMessageInterfaceFactory;

    /**
     * @var SmsMessageRepositoryInterface
     */
    private $smsMessageRepositoryInterface;

    /**
     * @var Configuration
     */
    private $moduleConfig;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * TransactionalMessageEnqueuer constructor.
     *
     * @param Logger $logger
     * @param SmsMessageInterfaceFactory $smsMessageInterfaceFactory
     * @param SmsMessageRepositoryInterface $smsMessageRepositoryInterface
     * @param Configuration $moduleConfig
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Logger $logger,
        SmsMessageInterfaceFactory $smsMessageInterfaceFactory,
        SmsMessageRepositoryInterface $smsMessageRepositoryInterface,
        Configuration $moduleConfig,
        SerializerInterface $serializer
    ) {
        $this->logger = $logger;
        $this->smsMessageInterfaceFactory = $smsMessageInterfaceFactory;
        $this->smsMessageRepositoryInterface = $smsMessageRepositoryInterface;
        $this->moduleConfig = $moduleConfig;
        $this->serializer = $serializer;
    }

    /**
     * Queue the item.
     *
     * @param QueueItemInterface $item
     */
    public function queue(QueueItemInterface $item)
    {
        $smsMessage = $this->smsMessageInterfaceFactory
            ->create()
            ->setStoreId($item->getStoreId())
            ->setWebsiteId($item->getWebsiteId())
            ->setTypeId($item->getTypeId())
            ->setStatus(0)
            ->setPhoneNumber($item->getPhoneNumber())
            ->setEmail($item->getEmail())
            ->setAdditionalData(
                $this->serialiseData($item->getAdditionalData())
            );

        $this->smsMessageRepositoryInterface
            ->save($smsMessage);
    }

    /**
     * Check if config allows sending of this message type.
     *
     * @param QueueItemInterface $item
     * @param int $storeId
     *
     * @return bool
     */
    public function canQueue(QueueItemInterface $item, int $storeId)
    {
        return $this->moduleConfig->isTransactionalSmsEnabled($storeId) &&
            $this->moduleConfig->isSmsTypeEnabled($storeId, $item->getSmsConfigPath());
    }

    /**
     * Serialize the data.
     *
     * @param array $data
     *
     * @return string
     */
    private function serialiseData(array $data)
    {
        try {
            return $this->serializer->serialize($data);
        } catch (InvalidArgumentException $e) {
            $this->logger->debug((string) $e);
            return '';
        }
    }
}
