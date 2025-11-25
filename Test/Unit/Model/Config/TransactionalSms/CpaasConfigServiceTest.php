<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Config\TransactionalSms;

use Dotdigitalgroup\Email\Helper\Data as Helper;
use Dotdigitalgroup\Sms\Model\Apiconnector\Client;
use Dotdigitalgroup\Sms\Model\Apiconnector\SmsClientFactory;
use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Dotdigitalgroup\Sms\Model\Config\TransactionalSms\CpaasConfigService;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsMessage;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsMessageFactory;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CpaasConfigServiceTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var WriterInterface|MockObject
     */
    private $configWriter;

    /**
     * @var Helper|MockObject
     */
    private $helper;

    /**
     * @var SmsClientFactory|MockObject
     */
    private $smsClientFactory;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var SmsMessageFactory|MockObject
     */
    private $smsMessageResourceFactory;

    /**
     * @var ReinitableConfigInterface|MockObject
     */
    private $reinitableConfig;

    /**
     * @var CpaasConfigService
     */
    private $service;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->configWriter = $this->createMock(WriterInterface::class);
        $this->helper = $this->createMock(Helper::class);
        $this->smsClientFactory = $this->createMock(SmsClientFactory::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->smsMessageResourceFactory = $this->createMock(SmsMessageFactory::class);
        $this->reinitableConfig = $this->createMock(ReinitableConfigInterface::class);

        $this->service = new CpaasConfigService(
            $this->scopeConfig,
            $this->configWriter,
            $this->helper,
            $this->smsClientFactory,
            $this->storeManager,
            $this->smsMessageResourceFactory,
            $this->reinitableConfig
        );
    }

    public function testGetAPIUsersForEnabledWebsitesReturnsCorrectStructure(): void
    {
        $website1 = $this->createMock(Website::class);
        $website1->method('getId')->willReturn(1);
        $website2 = $this->createMock(Website::class);
        $website2->method('getId')->willReturn(2);
        $website3 = $this->createMock(Website::class);
        $website3->method('getId')->willReturn(3);

        $this->storeManager->expects($this->once())
            ->method('getWebsites')
            ->with(true)
            ->willReturn([$website1, $website2, $website3]);

        $this->helper->expects($this->exactly(3))
            ->method('isEnabled')
            ->willReturnMap([
                [1, true],
                [2, true],
                [3, false]
            ]);

        $this->helper->expects($this->exactly(2))
            ->method('getApiUsername')
            ->willReturnMap([
                [1, 'user1@example.com'],
                [2, 'user1@example.com']
            ]);

        $result = $this->service->getAPIUsersForEnabledWebsites();

        $this->assertArrayHasKey('user1@example.com', $result);
        $this->assertEquals([1, 2], $result['user1@example.com']['websiteIds']);
        $this->assertEquals(1, $result['user1@example.com']['websiteId']);
    }

    public function testConfigureCpaasInboundRulesCreatesOptOutAndOptInRulesWhenEnabled(): void
    {
        $websiteId = 1;
        $client = $this->createMock(Client::class);

        $this->smsClientFactory->expects($this->exactly(3))
            ->method('create')
            ->with($websiteId)
            ->willReturn($client);

        $this->setupWebsitesSharingApiUser($websiteId, [$websiteId]);
        $this->setupConsentConfig($websiteId, true, '', 0, '', 0);

        $client->expects($this->exactly(2))
            ->method('getInboundRules')
            ->willReturn([]);

        $optOutResponse = (object)['id' => 'opt-out-rule-123'];
        $optInResponse = (object)['id' => 'opt-in-rule-456'];

        $client->expects($this->exactly(2))
            ->method('postInboundRule')
            ->willReturnMap([
                ['STOP', 'out', $optOutResponse],
                ['START', 'in', $optInResponse]
            ]);

        $this->configWriter->expects($this->exactly(4))
            ->method('save')
            ->willReturnCallback(function ($path, $value) {
                static $callCount = 0;
                $callCount++;

                if ($callCount === 1) {
                    $this->assertEquals(ConfigInterface::XML_PATH_CPAAS_OPTOUT_ID, $path);
                    $this->assertEquals('opt-out-rule-123', $value);
                } elseif ($callCount === 2) {
                    $this->assertEquals(ConfigInterface::XML_PATH_CPAAS_OPTOUT_GENERATED, $path);
                    $this->assertEquals(1, $value);
                } elseif ($callCount === 3) {
                    $this->assertEquals(ConfigInterface::XML_PATH_CPAAS_OPTIN_ID, $path);
                    $this->assertEquals('opt-in-rule-456', $value);
                } elseif ($callCount === 4) {
                    $this->assertEquals(ConfigInterface::XML_PATH_CPAAS_OPTIN_GENERATED, $path);
                    $this->assertEquals(1, $value);
                }
            });

        $this->service->configureCpaasInboundRules($websiteId);
    }

    public function testConfigureCpaasInboundRulesUsesExistingRules(): void
    {
        $websiteId = 1;
        $client = $this->createMock(Client::class);

        $this->smsClientFactory->expects($this->exactly(3))
            ->method('create')
            ->with($websiteId)
            ->willReturn($client);

        $this->setupWebsitesSharingApiUser($websiteId, [$websiteId]);
        $this->setupConsentConfig($websiteId, true, '', 0, '', 0);

        $optOutRule = (object)[
            'id' => 'existing-opt-out',
            'channel' => 'sms',
            'inbound' => '*',
            'keyword' => 'STOP',
            'actionData' => (object)['opt' => 'out']
        ];

        $optInRule = (object)[
            'id' => 'existing-opt-in',
            'channel' => 'sms',
            'inbound' => '*',
            'keyword' => 'START',
            'actionData' => (object)['opt' => 'in']
        ];

        $client->expects($this->exactly(2))
            ->method('getInboundRules')
            ->willReturn([$optOutRule, $optInRule]);

        $client->expects($this->never())
            ->method('postInboundRule');

        $this->configWriter->expects($this->exactly(4))
            ->method('save');

        $this->service->configureCpaasInboundRules($websiteId);
    }

    public function testConfigureCpaasInboundRulesDeletesGeneratedRulesWhenDisabled(): void
    {
        $websiteId = 1;
        $client = $this->createMock(Client::class);

        $this->smsClientFactory->expects($this->once())
            ->method('create')
            ->with($websiteId)
            ->willReturn($client);

        $this->setupWebsitesSharingApiUser($websiteId, [$websiteId]);
        $this->setupConsentConfig($websiteId, false, 'opt-out-id', 1, 'opt-in-id', 1);

        $client->expects($this->exactly(2))
            ->method('deleteInboundRule')
            ->willReturnCallback(function ($ruleId) {
                static $callCount = 0;
                $callCount++;

                if ($callCount === 1) {
                    $this->assertEquals('opt-out-id', $ruleId);
                } else {
                    $this->assertEquals('opt-in-id', $ruleId);
                }
            });

        $this->configWriter->expects($this->exactly(4))
            ->method('save');

        $this->service->configureCpaasInboundRules($websiteId);
    }

    public function testConfigureCpaasInboundRulesDoesNotDeleteManuallyCreatedRulesWhenDisabled(): void
    {
        $websiteId = 1;
        $client = $this->createMock(Client::class);

        $this->smsClientFactory->expects($this->once())
            ->method('create')
            ->with($websiteId)
            ->willReturn($client);

        $this->setupWebsitesSharingApiUser($websiteId, [$websiteId]);
        $this->setupConsentConfig($websiteId, false, 'opt-out-id', 0, 'opt-in-id', 0);

        $client->expects($this->never())
            ->method('deleteInboundRule');

        $this->service->configureCpaasInboundRules($websiteId);
    }

    public function testConfigureCpaasProfileDefaultsSkipsWhenConsentDisabled(): void
    {
        $websiteId = 1;

        $this->setupWebsitesSharingApiUser($websiteId, [$websiteId]);
        $this->setupConsentConfig($websiteId, false, '', 0, '', 0);

        $this->smsClientFactory->expects($this->never())
            ->method('create');

        $this->service->configureCpaasProfileDefaults($websiteId);
    }

    public function testConfigureCpaasProfileDefaultsUpdatesProfilesAndDefaults(): void
    {
        $websiteId = 1;
        $client = $this->createMock(Client::class);

        $this->smsClientFactory->expects($this->once())
            ->method('create')
            ->with($websiteId)
            ->willReturn($client);

        $this->setupWebsitesSharingApiUser($websiteId, [$websiteId]);
        $this->setupConsentConfig($websiteId, true, '', 0, '', 0);

        $smsResource = $this->createMock(SmsMessage::class);
        $this->smsMessageResourceFactory->expects($this->once())
            ->method('create')
            ->willReturn($smsResource);

        $smsResource->expects($this->once())
            ->method('getUniquePhoneNumbers')
            ->willReturn(['+1234567890', '+0987654321']);

        $client->expects($this->once())
            ->method('updateProfilesOptInDefaults')
            ->with(['sms' => true]);

        $profileWithoutOptIn = (object)[
            'id' => 'profile-1',
            'phoneNumber' => '+1234567890'
        ];

        $profileWithOptIn = (object)[
            'id' => 'profile-2',
            'phoneNumber' => '+0987654321',
            '_optIn' => (object)['sms' => true]
        ];

        $profileNotInMessages = (object)[
            'id' => 'profile-3',
            'phoneNumber' => '+1111111111'
        ];

        $client->expects($this->once())
            ->method('getProfiles')
            ->with('?phoneNumberCountryCode=~US')
            ->willReturn([$profileWithoutOptIn, $profileWithOptIn, $profileNotInMessages]);

        $client->expects($this->once())
            ->method('updateProfileOptIn')
            ->with('profile-1', ['channels' => ['sms']]);

        $this->service->configureCpaasProfileDefaults($websiteId);
    }

    public function testSaveCpaasProfilesStatusUpdatesAllSharingWebsites(): void
    {
        $websiteId = 1;
        $status = 'configured';

        $this->setupWebsitesSharingApiUser($websiteId, [1, 2, 3]);

        $this->configWriter->expects($this->exactly(3))
            ->method('save')
            ->willReturnCallback(function ($path, $value, $scope, $scopeId) use ($status) {
                $this->assertEquals(ConfigInterface::XML_PATH_CPAAS_PROFILES_STATUS, $path);
                $this->assertEquals($status, $value);
                $this->assertEquals(ScopeInterface::SCOPE_WEBSITES, $scope);
                $this->assertContains($scopeId, [1, 2, 3]);
            });

        $this->reinitableConfig->expects($this->once())
            ->method('reinit');

        $this->service->saveCpaasProfilesStatus($websiteId, $status);
    }

    private function setupConsentConfig(
        int $websiteId,
        bool $enabled,
        string $optOutId,
        int $optOutGenerated,
        string $optInId,
        int $optInGenerated
    ): void {
        $this->scopeConfig->method('getValue')
            ->willReturnCallback(function (
                $path,
                $scope,
                $scopeId
            ) use (
                $websiteId,
                $enabled,
                $optOutId,
                $optOutGenerated,
                $optInId,
                $optInGenerated
            ) {
                if ($scopeId !== $websiteId) {
                    return false;
                }

                switch ($path) {
                    case ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_CONSENT_ENABLED:
                        return $enabled;
                    case ConfigInterface::XML_PATH_CPAAS_OPTOUT_ID:
                        return $optOutId;
                    case ConfigInterface::XML_PATH_CPAAS_OPTOUT_GENERATED:
                        return $optOutGenerated;
                    case ConfigInterface::XML_PATH_CPAAS_OPTIN_ID:
                        return $optInId;
                    case ConfigInterface::XML_PATH_CPAAS_OPTIN_GENERATED:
                        return $optInGenerated;
                    default:
                        return false;
                }
            });
    }

    private function setupWebsitesSharingApiUser(int $websiteId, array $websiteIds): void
    {
        $websites = [];
        foreach ($websiteIds as $id) {
            $website = $this->createMock(Website::class);
            $website->method('getId')->willReturn($id);
            $websites[] = $website;
        }

        $this->storeManager->method('getWebsites')
            ->willReturn($websites);

        $this->helper->method('isEnabled')
            ->willReturn(true);

        $this->helper->method('getApiUsername')
            ->willReturn('user@example.com');
    }
}
