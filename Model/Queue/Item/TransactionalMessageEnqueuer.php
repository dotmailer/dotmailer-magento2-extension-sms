<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\Item;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Api\Data\SmsOrderInterfaceFactory;
use Dotdigitalgroup\Sms\Api\SmsOrderRepositoryInterface;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use InvalidArgumentException;
use Magento\Framework\Serialize\SerializerInterface;

class TransactionalMessageEnqueuer
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SmsOrderInterfaceFactory
     */
    private $smsOrderInterfaceFactory;

    /**
     * @var SmsOrderRepositoryInterface
     */
    private $smsOrderRepositoryInterface;

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
     * @param SmsOrderInterfaceFactory $smsOrderInterfaceFactory
     * @param SmsOrderRepositoryInterface $smsOrderRepositoryInterface
     * @param Configuration $moduleConfig
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Logger $logger,
        SmsOrderInterfaceFactory $smsOrderInterfaceFactory,
        SmsOrderRepositoryInterface $smsOrderRepositoryInterface,
        Configuration $moduleConfig,
        SerializerInterface $serializer
    ) {
        $this->logger = $logger;
        $this->smsOrderInterfaceFactory = $smsOrderInterfaceFactory;
        $this->smsOrderRepositoryInterface = $smsOrderRepositoryInterface;
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
        $smsOrder = $this->smsOrderInterfaceFactory
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

        $this->smsOrderRepositoryInterface
            ->save($smsOrder);
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
