<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Test\Unit\Model\SmsSubscriber;

use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Customer\RegistrationStoreResolver;
use Dotdigitalgroup\Sms\Model\SmsSubscriber\StoreResolver;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class StoreResolverTest extends TestCase
{
    #[DataProvider('contactStores')]
    public function testStoreSelectionDoesNotOverwriteTheFirstContactMarker(
        int $customerId,
        ?int $firstContactStore,
        int $expectedStore
    ): void {
        $contact = $this->getMockBuilder(Contact::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $contact->setData(['website_id' => 1, 'customer_id' => $customerId, 'store_id' => $firstContactStore]);
        $registrationResolver = $this->createMock(RegistrationStoreResolver::class);
        $registrationResolver->method('getWebsiteStoreIds')->willReturn([1, 2, 3]);
        $registrationResolver->method('getFallbackStoreId')->willReturn(1);
        $registrationResolver->expects($customerId ? $this->once() : $this->never())->method('resolve')
            ->with($customerId, 1)->willReturn(2);

        $this->assertSame($expectedStore, (new StoreResolver($registrationResolver))->resolve($contact));
        $this->assertSame($firstContactStore, $contact->getData('store_id'));
    }

    public static function contactStores(): array
    {
        return [
            'customer uses registration store not contact store' => [10, 3, 2],
            'non-customer uses first-contact store' => [0, 3, 3],
            'non-customer missing store uses website default' => [0, null, 1],
            'non-customer admin store uses website default' => [0, 0, 1],
            'non-customer cross-website store uses website default' => [0, 9, 1]
        ];
    }

    public function testCannotPublishWithoutAWebsiteLocalFallbackStore(): void
    {
        $contact = $this->getMockBuilder(Contact::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $contact->setData(['website_id' => 1, 'customer_id' => 0, 'store_id' => 0]);
        $registrationResolver = $this->createStub(RegistrationStoreResolver::class);
        $registrationResolver->method('getWebsiteStoreIds')->willReturn([]);
        $registrationResolver->method('getFallbackStoreId')->willReturn(0);

        $this->expectException(LocalizedException::class);
        (new StoreResolver($registrationResolver))->resolve($contact);
    }
}
