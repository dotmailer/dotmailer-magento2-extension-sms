<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Setup\Patch\Data;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Api\Data\SmsMessageInterface;
use Dotdigitalgroup\Sms\Api\SmsMessageRepositoryInterface;
use Dotdigitalgroup\Sms\Model\Apiconnector\SmsClientFactory;
use Dotdigitalgroup\Sms\Model\Message\MessageBuilder;
use Dotdigitalgroup\Sms\Model\Queue\SmsMessageQueueManager;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsMessage\CollectionFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Stdlib\DateTime;

class MigratePendingSmsMessages implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var SmsClientFactory
     */
    private $smsClientFactory;

    /**
     * @var MessageBuilder
     */
    private $messageBuilder;

    /**
     * @var SmsMessageRepositoryInterface
     */
    private $smsMessageRepository;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CollectionFactory $collectionFactory
     * @param SmsClientFactory $smsClientFactory
     * @param MessageBuilder $messageBuilder
     * @param SmsMessageRepositoryInterface $smsMessageRepository
     * @param DateTime $dateTime
     * @param Logger $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CollectionFactory $collectionFactory,
        SmsClientFactory $smsClientFactory,
        MessageBuilder $messageBuilder,
        SmsMessageRepositoryInterface $smsMessageRepository,
        DateTime $dateTime,
        Logger $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->collectionFactory = $collectionFactory;
        $this->smsClientFactory = $smsClientFactory;
        $this->messageBuilder = $messageBuilder;
        $this->smsMessageRepository = $smsMessageRepository;
        $this->dateTime = $dateTime;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        try {
            $pendingMessages = $this->getPendingMessages();

            if ($pendingMessages->getSize() === 0) {
                $this->logger->info('No pending SMS messages to migrate');
                $this->moduleDataSetup->endSetup();
                return $this;
            }

            $this->logger->info(sprintf('Found %d pending SMS messages to process', $pendingMessages->getSize()));

            $processed = 0;
            $failed = 0;

            foreach ($pendingMessages as $message) {
                try {
                    $this->processPendingMessage($message);
                    $processed++;
                } catch (\Exception $e) {
                    $failed++;
                    $this->logger->error(
                        sprintf('Failed to process pending SMS message ID: %s', $message->getId()),
                        [(string) $e]
                    );
                }
            }

            $this->logger->info(sprintf(
                'Migration complete. Processed: %d, Failed: %d',
                $processed,
                $failed
            ));
        } catch (\Exception $e) {
            $this->logger->error('SMS migration patch failed', [(string) $e]);
        }

        $this->moduleDataSetup->endSetup();
        return $this;
    }

    /**
     * Get pending messages from old queue system.
     *
     * @return \Dotdigitalgroup\Sms\Model\ResourceModel\SmsMessage\Collection
     */
    private function getPendingMessages()
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('status', SmsMessageQueueManager::SMS_STATUS_PENDING)
            ->addFieldToFilter('phone_number', ['notnull' => true]);
    }

    /**
     * Process individual pending message.
     *
     * @param SmsMessageInterface $message
     * @return void
     * @throws \Exception
     */
    private function processPendingMessage(SmsMessageInterface $message): void
    {
        $client = $this->smsClientFactory->create($message->getWebsiteId());
        if (!$client) {
            $this->logger->error('Failed to create SMS client', [
                'website_id' => $message->getWebsiteId(),
                'message_id' => $message->getId()
            ]);
            return;
        }

        $messagePayload = $this->messageBuilder->buildMessage($message);
        $response = $client->sendSmsSingle($messagePayload);

        $this->updateMessageWithResponse($message, $messagePayload, $response);
    }

    /**
     * Update message with API response.
     *
     * @param SmsMessageInterface $message
     * @param array $messagePayload
     * @param mixed $response
     * @return void
     */
    private function updateMessageWithResponse(SmsMessageInterface $message, array $messagePayload, $response): void
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

        $this->smsMessageRepository->save($message);
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
