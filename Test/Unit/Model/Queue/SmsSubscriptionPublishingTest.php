<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Queue;

use Dotdigitalgroup\Email\Controller\Customer\Newsletter;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Customer\RegistrationStoreResolver;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Sms\Model\Consent\ConsentManager;
use Dotdigitalgroup\Sms\Model\Queue\Message\SmsSubscriptionData;
use Dotdigitalgroup\Sms\Model\Queue\Message\SmsSubscriptionDataFactory;
use Dotdigitalgroup\Sms\Model\Queue\Publisher\SmsMessagePublisher;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\Collection;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\CollectionFactory;
use Dotdigitalgroup\Sms\Model\SmsContactFactory;
use Dotdigitalgroup\Sms\Model\SmsSubscriber\StoreResolver;
use Dotdigitalgroup\Sms\Model\Subscriber;
use Dotdigitalgroup\Sms\Observer\Customer\Register;
use Dotdigitalgroup\Sms\Observer\Sales\CheckoutMarketingConsentObserver;
use Dotdigitalgroup\Sms\Plugin\Customer\Account\NewsletterControllerPlugin;
use Dotdigitalgroup\Sms\Plugin\Customer\Controller\Adminhtml\Index\Save;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Controller\Adminhtml\Index\Save as CustomerSave;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SmsSubscriptionPublishingTest extends TestCase
{
    /**
     * @var Contact
     */
    private Contact $contact;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var ContactResource
     */
    private ContactResource $resource;

    /**
     * @var SmsSubscriptionDataFactory
     */
    private SmsSubscriptionDataFactory $messageFactory;

    /**
     * @var PublisherInterface
     */
    private PublisherInterface $publisher;

    /**
     * @var StoreResolver
     */
    private StoreResolver $storeResolver;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var ConsentManager
     */
    private ConsentManager $consentManager;

    /**
     * @var SmsMessagePublisher
     */
    private SmsMessagePublisher $smsMessagePublisher;

    protected function setUp(): void
    {
        $this->contact = $this->getMockBuilder(Contact::class)->disableOriginalConstructor()
            ->onlyMethods([])->getMock();
        $this->contact->setData([
            'id' => 15,
            'website_id' => 1,
            'store_id' => 3,
            'customer_id' => 10,
            'email' => 'contact@example.com',
            'mobile_number' => '447123456789'
        ]);
        $collection = $this->createStub(Collection::class);
        $collection->method('loadByCustomerEmail')->willReturn($this->contact);
        $collection->method('loadByCustomerId')->willReturn($this->contact);
        $this->collectionFactory = $this->createStub(CollectionFactory::class);
        $this->collectionFactory->method('create')->willReturn($collection);
        $this->resource = $this->createStub(ContactResource::class);
        $this->messageFactory = $this->createStub(SmsSubscriptionDataFactory::class);
        $this->messageFactory->method('create')->willReturnCallback(function () {
            return new SmsSubscriptionData();
        });
        $this->publisher = $this->createMock(PublisherInterface::class);
        $registrationResolver = $this->createStub(RegistrationStoreResolver::class);
        $registrationResolver->method('resolve')->willReturn(2);
        $registrationResolver->method('getWebsiteStoreIds')->willReturn([1, 2, 3, 4]);
        $this->storeResolver = new StoreResolver($registrationResolver);
        $this->logger = $this->createMock(Logger::class);
        $this->logger->expects($this->never())->method('error');
        $this->logger->expects($this->never())->method('debug');
        $this->consentManager = $this->createStub(ConsentManager::class);
        $this->smsMessagePublisher = $this->createStub(SmsMessagePublisher::class);
    }

    public function testRegistrationPublishesRegistrationStoreNotContactStore(): void
    {
        $request = $this->createStub(Http::class);
        $request->method('getPost')->willReturn(new DataObject(['is_sms_subscribed' => true]));
        $request->method('get')->willReturn('447123456789');
        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        $customer = $this->getMockBuilder(Customer::class)->disableOriginalConstructor()
            ->onlyMethods([])->getMock();
        $customer->setData(['store_id' => 2, 'website_id' => 1, 'email' => 'contact@example.com']);
        $this->expectMessage('subscribe', 2);

        $observer = new Register(
            $this->logger,
            $this->collectionFactory,
            $this->resource,
            $this->consentManager,
            $context,
            $this->publisher,
            $this->messageFactory,
            $this->smsMessagePublisher,
            $this->storeResolver
        );
        $observer->execute(new Observer(['event' => new Event(['customer' => $customer])]));
    }

    #[DataProvider('checkoutContacts')]
    public function testCheckoutPublishesResolvedStoreNotOrderStore(int $customerId, int $expectedStore): void
    {
        $this->contact->setData('customer_id', $customerId);
        $session = $this->createStub(CheckoutSession::class);
        $session->method('getData')->willReturnMap([
            ['dd_sms_marketing_consent_checkbox', false, true],
            ['dd_sms_marketing_consent_telephone', false, '447123456789']
        ]);
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $order->setData([
            'store_id' => 4,
            'customer_email' => 'contact@example.com',
            'customer_firstname' => 'Test',
            'customer_lastname' => 'Contact'
        ]);
        $this->expectMessage('subscribe', $expectedStore);

        $observer = new CheckoutMarketingConsentObserver(
            $this->logger,
            $this->createStub(SmsContactFactory::class),
            $this->resource,
            $this->collectionFactory,
            $session,
            $this->storeManager(),
            $this->consentManager,
            $this->publisher,
            $this->messageFactory,
            $this->smsMessagePublisher,
            $this->storeResolver
        );
        $observer->execute(new Observer(['event' => new Event(['order' => $order])]));
        $this->assertSame(3, $this->contact->getData('store_id'));
    }

    #[DataProvider('accountActions')]
    public function testAccountPublishesRegistrationStoreForEverySubscriptionAction(
        bool $subscribed,
        int $status,
        string $type
    ): void {
        $this->contact->setData('sms_subscriber_status', $status);
        $request = $this->createStub(Http::class);
        $request->method('getParam')->willReturnMap([
            ['is_sms_subscribed', null, $subscribed],
            ['mobile_number', null, '447987654321']
        ]);
        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        $helper = $this->createStub(Data::class);
        $helper->method('isEnabled')->willReturn(true);
        $validator = $this->createStub(Validator::class);
        $validator->method('validate')->willReturn(true);
        $customer = $this->getMockBuilder(Customer::class)->disableOriginalConstructor()
            ->onlyMethods([])->getMock();
        $customer->setData('email', 'contact@example.com');
        $session = $this->createStub(Session::class);
        $session->method('getCustomer')->willReturn($customer);
        $this->expectMessage($type, 2);

        $plugin = new NewsletterControllerPlugin(
            $helper,
            $this->logger,
            $this->resource,
            $this->collectionFactory,
            $session,
            $validator,
            $this->storeManager(),
            $this->consentManager,
            $this->messageFactory,
            $this->publisher,
            $this->smsMessagePublisher,
            $context,
            $this->storeResolver
        );
        $plugin->afterExecute($this->createStub(Newsletter::class), $this->createStub(Redirect::class));
    }

    #[DataProvider('adminActions')]
    public function testAdminPublishesRegistrationStoreForSubscribeAndUnsubscribe(bool $subscribed, string $type): void
    {
        $this->contact->setData('sms_subscriber_status', Subscriber::STATUS_SUBSCRIBED);
        $request = $this->createStub(Http::class);
        $request->method('getParam')->willReturnMap([
            ['is_subscribed', null, $subscribed],
            ['customer_id', null, 10],
            ['mobile_number', null, '447123456789']
        ]);
        $controller = $this->createStub(CustomerSave::class);
        $controller->method('getRequest')->willReturn($request);
        $this->expectMessage($type, 2);

        $plugin = new Save(
            $this->logger,
            $this->collectionFactory,
            $this->resource,
            $this->publisher,
            $this->messageFactory,
            $this->storeResolver
        );
        $plugin->afterExecute($controller, $this->createStub(Redirect::class));
    }

    public static function checkoutContacts(): array
    {
        return ['customer registration store' => [10, 2], 'guest first-contact store' => [0, 3]];
    }

    public static function accountActions(): array
    {
        return [
            'opt in' => [true, Subscriber::STATUS_UNSUBSCRIBED, 'subscribe'],
            'opt out' => [false, Subscriber::STATUS_SUBSCRIBED, 'unsubscribe'],
            'update mobile number' => [true, Subscriber::STATUS_SUBSCRIBED, 'subscribe']
        ];
    }

    public static function adminActions(): array
    {
        return ['subscribe' => [true, 'subscribe'], 'unsubscribe' => [false, 'unsubscribe']];
    }

    private function expectMessage(string $type, int $storeId): void
    {
        $this->publisher->expects($this->once())->method('publish')
            ->with(Subscriber::TOPIC_SMS_SUBSCRIPTION, $this->callback(
                function (SmsSubscriptionData $message) use ($type, $storeId) {
                    return $message->getType() === $type
                        && $message->getWebsiteId() === 1
                        && $message->getStoreId() === $storeId;
                }
            ));
    }

    private function storeManager(): StoreManagerInterface
    {
        $store = $this->getMockBuilder(Store::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $store->setData(['store_id' => 4, 'website_id' => 1]);
        $manager = $this->createStub(StoreManagerInterface::class);
        $manager->method('getStore')->willReturn($store);
        return $manager;
    }
}
