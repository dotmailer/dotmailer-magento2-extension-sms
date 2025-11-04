<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\Consumer;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Api\Data\SmsMessageInterface;
use Dotdigitalgroup\Sms\Api\Data\SmsMessageInterfaceFactory;
use Dotdigitalgroup\Sms\Api\SmsMessageRepositoryInterface;
use Dotdigitalgroup\Sms\Model\Apiconnector\SmsClientFactory;
use Dotdigitalgroup\Sms\Model\Message\MessageBuilder;
use Dotdigitalgroup\Sms\Model\Queue\Message\SmsMessageData;
use Dotdigitalgroup\Sms\Model\Queue\SmsMessageQueueManager;
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
     * @param DateTime $dateTime
     * @param Logger $logger
     */
    public function __construct(
        SmsClientFactory $smsClientFactory,
        MessageBuilder $messageBuilder,
        SmsMessageInterfaceFactory $smsMessageInterfaceFactory,
        SmsMessageRepositoryInterface $smsMessageRepositoryInterface,
        DateTime $dateTime,
        Logger $logger
    ) {
        $this->smsClientFactory = $smsClientFactory;
        $this->messageBuilder = $messageBuilder;
        $this->smsMessageInterfaceFactory = $smsMessageInterfaceFactory;
        $this->smsMessageRepositoryInterface = $smsMessageRepositoryInterface;
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

            $messagePayload = $this->messageBuilder->buildMessage($message);
            $response = $client->sendSmsSingle($messagePayload);
            $this->saveMessageWithResponse($message, $messagePayload, $response);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send SMS', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
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
    private function saveMessageWithResponse(SmsMessageInterface $message, $messagePayload, $response): void
    {
        $messageId = $response->messageId ?? null;

        $message
            ->setMessageId($messageId)
            ->setContent($messagePayload['body'])
            ->setStatus(SmsMessageQueueManager::SMS_STATUS_IN_PROGRESS);

        if (!isset($response->messageId)) {
            $message->setStatus(SmsMessageQueueManager::SMS_STATUS_UNKNOWN);
        } elseif (isset($response->status)) {
            if ($response->status === 'delivered') {
                $message->setStatus(SmsMessageQueueManager::SMS_STATUS_DELIVERED);
                $message->setMessage($response->statusDetails->channelStatus->statusdescription);
                $message->setSentAt($this->dateTime->formatDate($response->sentOn));
            } elseif ($response->status === 'failed') {
                $message->setStatus(SmsMessageQueueManager::SMS_STATUS_FAILED);
                $message->setMessage($response->statusDetails->reason);
            }
        }

        $this->smsMessageRepositoryInterface->save($message);
    }
}
