<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\Consumer;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Model\Queue\Message\CpaasConfigMessage;
use Dotdigitalgroup\Sms\Model\Config\TransactionalSms\CpaasConfigService;

/**
 * Consumer for CPaaS configuration messages from the queue.
 */
class CpaasConfigConsumer
{
    /**
     * @var CpaasConfigService
     */
    private CpaasConfigService $cpaasConfigService;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @param CpaasConfigService $cpaasConfigService
     * @param Logger $logger
     */
    public function __construct(
        CpaasConfigService $cpaasConfigService,
        Logger $logger
    ) {
        $this->cpaasConfigService = $cpaasConfigService;
        $this->logger = $logger;
    }

    /**
     * Process CPaaS configuration message.
     *
     * @param CpaasConfigMessage $message
     * @return void
     */
    public function process(CpaasConfigMessage $message): void
    {
        $websiteId = $message->getWebsiteId();

        try {
            $this->cpaasConfigService->configureCpaasOptOutRule($websiteId);
            $this->cpaasConfigService->configureCpaasProfileDefaults($websiteId);
            $this->cpaasConfigService->saveCpaasProfilesStatus($websiteId, 'configured');

            $this->logger->info(
                sprintf('CPaaS configuration completed for website ID: %d', $websiteId)
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf(
                    'Error configuring CPaaS for website ID %d: %s',
                    $websiteId,
                    $e->getMessage()
                )
            );

            $this->cpaasConfigService->saveCpaasProfilesStatus($websiteId, 'error');
        }
    }
}
