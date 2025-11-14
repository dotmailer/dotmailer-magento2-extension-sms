<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Config\TransactionalSms;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Model\Config\TransactionalSms\CpaasConfigManager;
use Dotdigitalgroup\Sms\Model\Config\TransactionalSms\CpaasConfigService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CpaasConfigManagerTest extends TestCase
{
    /**
     * @var CpaasConfigService|MockObject
     */
    private $cpaasConfigService;

    /**
     * @var Data|MockObject
     */
    private $helper;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var Logger|MockObject
     */
    private $logger;

    /**
     * @var CpaasConfigManager
     */
    private $manager;

    protected function setUp(): void
    {
        $this->cpaasConfigService = $this->createMock(CpaasConfigService::class);
        $this->helper = $this->createMock(Data::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->logger = $this->createMock(Logger::class);

        $this->manager = new CpaasConfigManager(
            $this->cpaasConfigService,
            $this->helper,
            $this->scopeConfig,
            $this->storeManager,
            $this->logger
        );
    }

    public function testRunSuccessfullyConfiguresAllWebsites(): void
    {
        $apiUsers = [
            'user1@example.com' => [
                'websiteId' => 1,
                'websiteIds' => [1, 2]
            ],
            'user2@example.com' => [
                'websiteId' => 3,
                'websiteIds' => [3]
            ]
        ];

        $this->cpaasConfigService->expects($this->once())
            ->method('getAPIUsersForEnabledWebsites')
            ->willReturn($apiUsers);

        $this->cpaasConfigService->expects($this->exactly(4))
            ->method('saveCpaasProfilesStatus')
            ->willReturnCallback(function ($websiteId, $status) {
                static $callCount = 0;
                $callCount++;

                if ($callCount === 1) {
                    $this->assertEquals(1, $websiteId);
                    $this->assertEquals('pending', $status);
                } elseif ($callCount === 2) {
                    $this->assertEquals(1, $websiteId);
                    $this->assertEquals('configured', $status);
                } elseif ($callCount === 3) {
                    $this->assertEquals(3, $websiteId);
                    $this->assertEquals('pending', $status);
                } elseif ($callCount === 4) {
                    $this->assertEquals(3, $websiteId);
                    $this->assertEquals('configured', $status);
                }
            });

        $this->cpaasConfigService->expects($this->exactly(2))
            ->method('configureCpaasOptOutRule')
            ->willReturnCallback(function ($websiteId) {
                static $callCount = 0;
                $callCount++;
                $this->assertEquals($callCount === 1 ? 1 : 3, $websiteId);
            });

        $this->cpaasConfigService->expects($this->exactly(2))
            ->method('configureCpaasProfileDefaults')
            ->willReturnCallback(function ($websiteId) {
                static $callCount = 0;
                $callCount++;
                $this->assertEquals($callCount === 1 ? 1 : 3, $websiteId);
            });

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function ($message) {
                static $callCount = 0;
                $callCount++;

                if ($callCount === 1) {
                    $this->assertEquals('CPaaS configuration completed for website IDs: 1,2', $message);
                } else {
                    $this->assertEquals('CPaaS configuration completed for website IDs: 3', $message);
                }
            });

        $this->manager->run();
    }

    public function testRunHandlesExceptionAndLogsError(): void
    {
        $apiUsers = [
            'user@example.com' => [
                'websiteId' => 1,
                'websiteIds' => [1, 2]
            ]
        ];

        $this->cpaasConfigService->expects($this->once())
            ->method('getAPIUsersForEnabledWebsites')
            ->willReturn($apiUsers);

        $this->cpaasConfigService->expects($this->exactly(2))
            ->method('saveCpaasProfilesStatus')
            ->willReturnCallback(function ($websiteId, $status) {
                static $callCount = 0;
                $callCount++;

                $this->assertEquals(1, $websiteId);
                if ($callCount === 1) {
                    $this->assertEquals('pending', $status);
                } else {
                    $this->assertEquals('error', $status);
                }
            });

        $this->cpaasConfigService->expects($this->once())
            ->method('configureCpaasOptOutRule')
            ->with(1)
            ->willThrowException(new \Exception('Configuration error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error configuring CPaaS for website IDs 1,2: Configuration error');

        $this->manager->run();
    }

    public function testRunHandlesEmptyApiUsers(): void
    {
        $this->cpaasConfigService->expects($this->once())
            ->method('getAPIUsersForEnabledWebsites')
            ->willReturn([]);

        $this->cpaasConfigService->expects($this->never())
            ->method('configureCpaasOptOutRule');

        $this->logger->expects($this->never())
            ->method('info');

        $this->manager->run();
    }

    public function testRunHandlesExceptionDuringProfileDefaults(): void
    {
        $apiUsers = [
            'user@example.com' => [
                'websiteId' => 1,
                'websiteIds' => [1]
            ]
        ];

        $this->cpaasConfigService->expects($this->once())
            ->method('getAPIUsersForEnabledWebsites')
            ->willReturn($apiUsers);

        $this->cpaasConfigService->expects($this->exactly(2))
            ->method('saveCpaasProfilesStatus')
            ->willReturnCallback(function ($websiteId, $status) {
                static $callCount = 0;
                $callCount++;

                $this->assertEquals(1, $websiteId);
                if ($callCount === 1) {
                    $this->assertEquals('pending', $status);
                } else {
                    $this->assertEquals('error', $status);
                }
            });

        $this->cpaasConfigService->expects($this->once())
            ->method('configureCpaasOptOutRule')
            ->with(1);

        $this->cpaasConfigService->expects($this->once())
            ->method('configureCpaasProfileDefaults')
            ->with(1)
            ->willThrowException(new \Exception('Profile error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error configuring CPaaS for website IDs 1: Profile error');

        $this->manager->run();
    }
}
