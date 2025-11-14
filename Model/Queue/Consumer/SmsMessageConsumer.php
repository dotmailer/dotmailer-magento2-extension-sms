<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\Consumer;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Order;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory;
use Dotdigitalgroup\Sms\Api\Data\SmsMessageInterface;
use Dotdigitalgroup\Sms\Api\Data\SmsMessageInterfaceFactory;
use Dotdigitalgroup\Sms\Api\SmsMessageRepositoryInterface;
use Dotdigitalgroup\Sms\Model\Apiconnector\SmsClientFactory;
use Dotdigitalgroup\Sms\Model\Message\MessageBuilder;
use Dotdigitalgroup\Sms\Model\Queue\Message\SmsMessageData;
use Dotdigitalgroup\Sms\Model\Queue\SmsMessageQueueManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime;

class SmsMessageConsumer
{
    /**
     * @var SmsClientFactory
     */
    private $smsClientFactory;

    /**
     * @var MessageBuilder
     */
    private $messageBuilder;

    /**
     * @var SmsMessageInterfaceFactory
     */
    private $smsMessageInterfaceFactory;

    /**
     * @var SmsMessageRepositoryInterface
     */
    private $smsMessageRepositoryInterface;

    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * SmsMessageConsumer constructor.
     *
     * @param SmsClientFactory $smsClientFactory
     * @param MessageBuilder $messageBuilder
     * @param SmsMessageInterfaceFactory $smsMessageInterfaceFactory
     * @param SmsMessageRepositoryInterface $smsMessageRepositoryInterface
     * @param CollectionFactory $orderCollectionFactory
     * @param DateTime $dateTime
     * @param Logger $logger
     */
    public function __construct(
        SmsClientFactory $smsClientFactory,
        MessageBuilder $messageBuilder,
        SmsMessageInterfaceFactory $smsMessageInterfaceFactory,
        SmsMessageRepositoryInterface $smsMessageRepositoryInterface,
        CollectionFactory $orderCollectionFactory,
        DateTime $dateTime,
        Logger $logger
    ) {
        $this->smsClientFactory = $smsClientFactory;
        $this->messageBuilder = $messageBuilder;
        $this->smsMessageInterfaceFactory = $smsMessageInterfaceFactory;
        $this->smsMessageRepositoryInterface = $smsMessageRepositoryInterface;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->dateTime = $dateTime;
        $this->logger = $logger;
    }

    /**
     * Process SMS message from queue.
     *
     * @param SmsMessageData $messageData
     * @return void
     * @throws \Exception
     */
    public function process(SmsMessageData $messageData): void
    {
        $message = null;
        try {
            $message = $this->smsMessageInterfaceFactory->create()
                ->setWebsiteId($messageData->getWebsiteId())
                ->setStoreId($messageData->getStoreId())
                ->setTypeId($messageData->getTypeId())
                ->setOrderId($messageData->getOrderId())
                ->setPhoneNumber($messageData->getPhoneNumber())
                ->setEmail($messageData->getEmail())
                ->setAdditionalData($messageData->getAdditionalData());

            $client = $this->smsClientFactory->create($message->getWebsiteId());
            if (!$client) {
                $this->logger->error('Failed to create SMS client', [
                    'website_id' => $message->getWebsiteId()
                ]);
                return;
            }

            $order = $this->loadOrderById($messageData->getOrderId());

            if ($this->orderRequiresOptInButHasNoOptIn($order)) {
                $this->logger->info('Transactional SMS send skipped - opt-in was required but was not provided', [
                    'website_id' => $message->getWebsiteId(),
                    'order_id' => $message->getOrderId()
                ]);
                return;
            }

            $messagePayload = $this->messageBuilder->buildMessage($message, $this->orderRequiresOptIn($order));
            $response = $client->sendSmsSingle($messagePayload);
            $this->saveMessageWithResponse($message, $messagePayload, $response);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send SMS', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            if ($message !== null) {
                $message->setStatus(SmsMessageQueueManager::SMS_STATUS_FAILED);
                $this->smsMessageRepositoryInterface->save($message);
            }
        }
    }

    /**
     * Save SMS message with API response data.
     *
     * @param SmsMessageInterface $message
     * @param array $messagePayload
     * @param mixed $response
     * @return void
     */
    private function saveMessageWithResponse(SmsMessageInterface $message, array $messagePayload, $response): void
    {
        $messageId = $response->messageId ?? null;
        $messageStatus = SmsMessageQueueManager::SMS_STATUS_IN_PROGRESS;

        $message
            ->setMessageId($messageId)
            ->setContent($messagePayload['body']);

        if (!isset($response->messageId)) {
            $messageStatus = SmsMessageQueueManager::SMS_STATUS_UNKNOWN;
        } elseif (isset($response->status)) {
            if ($response->status === 'delivered') {
                $messageStatus = SmsMessageQueueManager::SMS_STATUS_DELIVERED;
                $message->setMessage($response->statusDetails->channelStatus->statusdescription);
                $message->setSentAt($this->dateTime->formatDate($response->sentOn));
            } elseif ($response->status === 'failed') {
                $messageStatus = SmsMessageQueueManager::SMS_STATUS_FAILED;
                $message->setMessage($response->statusDetails->reason);
            }
        }

        $message->setStatus($messageStatus);
        $this->smsMessageRepositoryInterface->save($message);
    }

    /**
     * Load order by ID from collection.
     *
     * @param int $orderId
     * @return Order
     * @throws LocalizedException
     */
    private function loadOrderById(int $orderId)
    {
        $collection = $this->orderCollectionFactory
            ->create()
            ->getOrdersFromIds([$orderId])
            ->setPageSize(1);

        $item = $collection->getFirstItem();
        if (!$item || !$item->getId()) {
            $this->logger->error('Order not found for SMS opt-in check', [
                'order_id' => $orderId
            ]);

            throw new \Magento\Framework\Exception\LocalizedException(
                __('Order not found for SMS opt-in check, Order Id %1', $orderId)
            );
        }

        return $item;
    }

    /**
     * Check if order requires opt-in but customer has not opted in.
     *
     * @param Order $item
     * @return bool
     */
    private function orderRequiresOptInButHasNoOptIn(Order $item): bool
    {
        return (bool) $item->getData('sms_transactional_requires_opt_in')
            && ! $item->getData('sms_transactional_opt_in');
    }

    /**
     * Check if order requires opt-in and customer has opted in.
     *
     * @param Order $item
     * @return bool
     */
    private function orderRequiresOptIn(Order $item): bool
    {
        return (bool) $item->getData('sms_transactional_requires_opt_in') && $item->getData('sms_transactional_opt_in');
    }
}
