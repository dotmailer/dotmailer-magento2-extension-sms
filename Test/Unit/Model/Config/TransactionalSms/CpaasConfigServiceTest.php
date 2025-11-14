<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Config\TransactionalSms;

use Dotdigitalgroup\Email\Helper\Data as Helper;
use Dotdigitalgroup\Sms\Model\Apiconnector\SmsClientFactory;
use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Dotdigitalgroup\Sms\Model\Config\TransactionalSms\CpaasConfigService;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsMessageFactory;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsMessage;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\LocalizedException;
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

        $this->storeManager->expects($this->once())
            ->method('getWebsites')
            ->with(true)
            ->willReturn([$website1, $website2]);

        $this->helper->expects($this->exactly(2))
            ->method('isEnabled')
            ->willReturn(true);

        $this->helper->expects($this->exactly(2))
            ->method('getApiUsername')
            ->willReturn('user1@example.com');

        $result = $this->service->getAPIUsersForEnabledWebsites();

        $this->assertArrayHasKey('user1@example.com', $result);
        $this->assertEquals([1, 2], $result['user1@example.com']['websiteIds']);
        $this->assertEquals(1, $result['user1@example.com']['websiteId']);
    }

    public function testConfigureCpaasOptOutRuleCreatesNewRuleWhenNotExists(): void
    {
        $websiteId = 1;
        $client = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getOptOutRules', 'postOptOutRule'])
            ->getMock();

        $this->smsClientFactory->expects($this->exactly(2))
            ->method('create')
            ->with($websiteId)
            ->willReturn($client);

        $this->setupConfigForOptOut($websiteId, true, '', false);

        $client->expects($this->once())
            ->method('getOptOutRules')
            ->willReturn([]);

        $response = (object)['id' => 'rule-123'];
        $client->expects($this->once())
            ->method('postOptOutRule')
            ->with('STOP')
            ->willReturn($response);

        $this->configWriter->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function ($path, $value, $scope, $scopeId) use ($websiteId) {
                static $callCount = 0;
                $callCount++;

                $this->assertEquals(ScopeInterface::SCOPE_WEBSITES, $scope);
                $this->assertEquals($websiteId, $scopeId);

                if ($callCount === 1) {
                    $this->assertEquals(ConfigInterface::XML_PATH_CPAAS_OPTOUT_ID, $path);
                    $this->assertEquals('rule-123', $value);
                } elseif ($callCount === 2) {
                    $this->assertEquals(ConfigInterface::XML_PATH_CPAAS_OPTOUT_GENERATED, $path);
                    $this->assertEquals(1, $value);
                }
            });

        $this->service->configureCpaasOptOutRule($websiteId);
    }

    public function testConfigureCpaasOptOutRuleUsesExistingRule(): void
    {
        $websiteId = 1;
        $client = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getOptOutRules'])
            ->getMock();

        $this->smsClientFactory->expects($this->exactly(2))
            ->method('create')
            ->with($websiteId)
            ->willReturn($client);

        $this->setupConfigForOptOut($websiteId, true, '', false);

        $existingRule = (object)[
            'id' => 'existing-rule-456',
            'channel' => 'sms',
            'inbound' => '*',
            'keyword' => 'STOP',
            'actionData' => (object)['opt' => 'out']
        ];

        $client->expects($this->once())
            ->method('getOptOutRules')
            ->willReturn([$existingRule]);

        $this->configWriter->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function ($path, $value, $scope, $scopeId) use ($websiteId) {
                static $callCount = 0;
                $callCount++;

                $this->assertEquals(ScopeInterface::SCOPE_WEBSITES, $scope);
                $this->assertEquals($websiteId, $scopeId);

                if ($callCount === 1) {
                    $this->assertEquals(ConfigInterface::XML_PATH_CPAAS_OPTOUT_ID, $path);
                    $this->assertEquals('existing-rule-456', $value);
                } elseif ($callCount === 2) {
                    $this->assertEquals(ConfigInterface::XML_PATH_CPAAS_OPTOUT_GENERATED, $path);
                    $this->assertEquals(0, $value);
                }
            });

        $this->service->configureCpaasOptOutRule($websiteId);
    }

    public function testConfigureCpaasOptOutRuleDeletesRuleWhenDisabled(): void
    {
        $websiteId = 1;
        $optOutId = 'rule-123';
        $client = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['deleteOptOutRule'])
            ->getMock();

        // Only called once because consent is disabled - no need to get/create rules
        $this->smsClientFactory->expects($this->once())
            ->method('create')
            ->with($websiteId)
            ->willReturn($client);

        $this->setupConfigForOptOut($websiteId, false, $optOutId, true);

        $client->expects($this->once())
            ->method('deleteOptOutRule')
            ->with($optOutId);

        $this->configWriter->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function ($path, $value, $scope, $scopeId) use ($websiteId) {
                static $callCount = 0;
                $callCount++;

                $this->assertEquals(ScopeInterface::SCOPE_WEBSITES, $scope);
                $this->assertEquals($websiteId, $scopeId);

                if ($callCount === 1) {
                    $this->assertEquals(ConfigInterface::XML_PATH_CPAAS_OPTOUT_ID, $path);
                    $this->assertEquals('', $value);
                } elseif ($callCount === 2) {
                    $this->assertEquals(ConfigInterface::XML_PATH_CPAAS_OPTOUT_GENERATED, $path);
                    $this->assertEquals(0, $value);
                }
            });

        $this->service->configureCpaasOptOutRule($websiteId);
    }

    public function testConfigureCpaasProfileDefaultsSkipsWhenConsentDisabled(): void
    {
        $websiteId = 1;

        $this->setupConfigForOptOut($websiteId, false, '', false);

        $this->smsClientFactory->expects($this->never())
            ->method('create');

        $this->service->configureCpaasProfileDefaults($websiteId);
    }

    public function testConfigureCpaasProfileDefaultsUpdatesProfiles(): void
    {
        $websiteId = 1;
        $client = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['updateProfilesOptInDefaults', 'getProfiles', 'updateProfileOptIn'])
            ->getMock();

        $this->smsClientFactory->expects($this->once())
            ->method('create')
            ->with($websiteId)
            ->willReturn($client);

        $this->setupConfigForOptOut($websiteId, true, '', false);

        $smsResource = $this->createMock(SmsMessage::class);
        $this->smsMessageResourceFactory->expects($this->once())
            ->method('create')
            ->willReturn($smsResource);

        $smsResource->expects($this->once())
            ->method('getUniquePhoneNumbers')
            ->willReturn(['+1234567890']);

        $client->expects($this->once())
            ->method('updateProfilesOptInDefaults')
            ->with(['sms' => true]);

        $profile = (object)[
            'id' => 'profile-123',
            'phoneNumber' => '+1234567890'
        ];

        $client->expects($this->once())
            ->method('getProfiles')
            ->with('?phoneNumberCountryCode=~US')
            ->willReturn([$profile]);

        $client->expects($this->once())
            ->method('updateProfileOptIn')
            ->with('profile-123', ['channels' => ['sms']]);

        $this->service->configureCpaasProfileDefaults($websiteId);
    }

    public function testSaveCpaasProfilesStatusUpdatesAllSharingWebsites(): void
    {
        $websiteId = 1;
        $status = 'configured';

        $this->setupWebsitesSharingApiUser($websiteId, [1, 2]);

        $this->configWriter->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function ($path, $value, $scope, $scopeId) use ($status) {
                static $callCount = 0;
                $callCount++;

                $this->assertEquals(ConfigInterface::XML_PATH_CPAAS_PROFILES_STATUS, $path);
                $this->assertEquals($status, $value);
                $this->assertEquals(ScopeInterface::SCOPE_WEBSITES, $scope);
                $this->assertEquals($callCount, $scopeId);
            });

        $this->reinitableConfig->expects($this->once())
            ->method('reinit');

        $this->service->saveCpaasProfilesStatus($websiteId, $status);
    }

    private function setupConfigForOptOut(int $websiteId, bool $enabled, string $optOutId, bool $generated): void
    {
        $this->setupWebsitesSharingApiUser($websiteId, [$websiteId]);

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnCallback(function ($path, $scope, $scopeId) use ($websiteId, $enabled, $optOutId, $generated) {
                if ($path === ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_CONSENT_ENABLED && $scopeId === $websiteId) {
                    return $enabled;
                }
                if ($path === ConfigInterface::XML_PATH_CPAAS_OPTOUT_ID && $scopeId === $websiteId) {
                    return $optOutId;
                }
                if ($path === ConfigInterface::XML_PATH_CPAAS_OPTOUT_GENERATED && $scopeId === $websiteId) {
                    return $generated;
                }
                return null;
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
