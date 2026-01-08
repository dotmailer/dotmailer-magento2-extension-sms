<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\Publisher;

use Dotdigitalgroup\Sms\Model\Queue\Message\CpaasConfigMessage;
use Dotdigitalgroup\Sms\Model\Queue\Message\CpaasConfigMessageFactory;
use Dotdigitalgroup\Sms\Model\Config\TransactionalSms\CpaasConfigService;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Publisher for CPaaS configuration messages.
 */
class CpaasConfigMessagePublisher
{
    public const TOPIC_CPAAS_CONFIG_MESSAGE = 'ddg.sms.cpaas.config';
    /**
     * @var PublisherInterface
     */
    private PublisherInterface $publisher;

    /**
     * @var CpaasConfigMessageFactory
     */
    private CpaasConfigMessageFactory $messageFactory;

    /**
     * @var CpaasConfigService
     */
    private CpaasConfigService $cpaasConfigService;

    /**
     * @param PublisherInterface $publisher
     * @param CpaasConfigMessageFactory $messageFactory
     * @param CpaasConfigService $cpaasConfigService
     */
    public function __construct(
        PublisherInterface $publisher,
        CpaasConfigMessageFactory $messageFactory,
        CpaasConfigService $cpaasConfigService
    ) {
        $this->publisher = $publisher;
        $this->messageFactory = $messageFactory;
        $this->cpaasConfigService = $cpaasConfigService;
    }

    /**
     * Publish CPaaS configuration message to the queue.
     *
     * @param int $websiteId
     * @return void
     */
    public function publish(int $websiteId): void
    {
        $this->cpaasConfigService->saveCpaasProfilesStatus($websiteId, 'pending');

        $message = $this->messageFactory->create(['data' => ['websiteId' => $websiteId]]);

        $this->publisher->publish(self::TOPIC_CPAAS_CONFIG_MESSAGE, $message);
    }
}
