<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Queue\Consumer;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Model\Config\TransactionalSms\CpaasConfigService;
use Dotdigitalgroup\Sms\Model\Queue\Consumer\CpaasConfigConsumer;
use Dotdigitalgroup\Sms\Model\Queue\Message\CpaasConfigMessage;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CpaasConfigConsumerTest extends TestCase
{
    /**
     * @var CpaasConfigService|MockObject
     */
    private $cpaasConfigService;

    /**
     * @var Logger|MockObject
     */
    private $logger;

    /**
     * @var CpaasConfigConsumer
     */
    private $consumer;

    protected function setUp(): void
    {
        $this->cpaasConfigService = $this->createMock(CpaasConfigService::class);
        $this->logger = $this->createMock(Logger::class);

        $this->consumer = new CpaasConfigConsumer(
            $this->cpaasConfigService,
            $this->logger
        );
    }

    public function testProcessSuccessfullyConfiguresCpaas(): void
    {
        $websiteId = 1;
        $message = $this->createMock(CpaasConfigMessage::class);
        $message->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->cpaasConfigService->expects($this->once())
            ->method('configureCpaasOptOutRule')
            ->with($websiteId);

        $this->cpaasConfigService->expects($this->once())
            ->method('configureCpaasProfileDefaults')
            ->with($websiteId);

        $this->cpaasConfigService->expects($this->once())
            ->method('saveCpaasProfilesStatus')
            ->with($websiteId, 'configured');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('CPaaS configuration completed for website ID: 1');

        $this->consumer->process($message);
    }

    public function testProcessHandlesExceptionAndLogsError(): void
    {
        $websiteId = 2;
        $exceptionMessage = 'Configuration failed';
        $message = $this->createMock(CpaasConfigMessage::class);
        $message->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->cpaasConfigService->expects($this->once())
            ->method('configureCpaasOptOutRule')
            ->with($websiteId)
            ->willThrowException(new \Exception($exceptionMessage));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error configuring CPaaS for website ID 2: Configuration failed');

        $this->cpaasConfigService->expects($this->once())
            ->method('saveCpaasProfilesStatus')
            ->with($websiteId, 'error');

        $this->consumer->process($message);
    }

    public function testProcessHandlesExceptionDuringProfileDefaults(): void
    {
        $websiteId = 3;
        $message = $this->createMock(CpaasConfigMessage::class);
        $message->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->cpaasConfigService->expects($this->once())
            ->method('configureCpaasOptOutRule')
            ->with($websiteId);

        $this->cpaasConfigService->expects($this->once())
            ->method('configureCpaasProfileDefaults')
            ->with($websiteId)
            ->willThrowException(new \RuntimeException('Profile error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error configuring CPaaS for website ID 3: Profile error');

        $this->cpaasConfigService->expects($this->once())
            ->method('saveCpaasProfilesStatus')
            ->with($websiteId, 'error');

        $this->consumer->process($message);
    }
}
