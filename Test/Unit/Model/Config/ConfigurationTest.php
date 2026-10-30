<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Config;

use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Api\StoreWebsiteRelationInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function testStoreListLookupUsesMagentoInheritanceAndSyncRemainsWebsiteScoped(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $lookups = [];
        $scopeConfig->expects($this->exactly(3))->method('getValue')
            ->willReturnCallback(function ($path, $scope, $scopeId) use (&$lookups) {
                $lookups[] = [$path, $scope, $scopeId];
                return $path === ConfigInterface::XML_PATH_CONNECTOR_SMS_SUBSCRIBER_SYNC_ENABLED ? 1 : 123;
            });
        $config = new Configuration(
            $scopeConfig,
            $this->createStub(WriterInterface::class),
            $this->createStub(RequestInterface::class),
            $this->createStub(ReinitableConfigInterface::class),
            $this->createStub(StoreWebsiteRelationInterface::class),
            $this->createStub(StoreManagerInterface::class)
        );

        $this->assertSame(123, $config->getListId(1, 2));
        $this->assertSame(123, $config->getListId(1));
        $this->assertTrue($config->isSmsSyncEnabled(1));
        $this->assertSame([
            [ConfigInterface::XML_PATH_CONNECTOR_SMS_SUBSCRIBER_ADDRESS_BOOK_ID, ScopeInterface::SCOPE_STORES, 2],
            [ConfigInterface::XML_PATH_CONNECTOR_SMS_SUBSCRIBER_ADDRESS_BOOK_ID, ScopeInterface::SCOPE_WEBSITE, 1],
            [ConfigInterface::XML_PATH_CONNECTOR_SMS_SUBSCRIBER_SYNC_ENABLED, ScopeInterface::SCOPE_WEBSITE, 1]
        ], $lookups);
    }
}
