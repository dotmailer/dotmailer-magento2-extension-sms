<?php

namespace Dotdigitalgroup\Sms\Test\Unit\Model\SmsSubscriber;

use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Cron\CronFromTimeSetter;
use Dotdigitalgroup\Sms\Model\SmsSubscriber\Unsubscriber;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact as ContactResource;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\Collection as ContactCollection;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\CollectionFactory as ContactCollectionFactory;
use PHPUnit\Framework\TestCase;

class UnsubscriberTest extends TestCase
{
    /**
     * @var Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var CronFromTimeSetter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cronFromTimeSetterMock;

    /**
     * @var ContactResource|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactResourceMock;

    /**
     * @var ContactCollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactCollectionFactoryMock;

    /**
     * @var Unsubscriber
     */
    private $model;

    /**
     * Prepare data
     */
    protected function setUp() :void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->cronFromTimeSetterMock = $this->createMock(CronFromTimeSetter::class);
        $this->contactResourceMock = $this->createMock(ContactResource::class);
        $this->contactCollectionFactoryMock = $this->createMock(ContactCollectionFactory::class);

        $this->model = new Unsubscriber(
            $this->loggerMock,
            $this->cronFromTimeSetterMock,
            $this->contactResourceMock,
            $this->contactCollectionFactoryMock
        );
    }

    /**
     * 4 recently modified contacts
     * 3 matches in the table
     * 1 of these changed status more recently so...
     * 2 are resubscribed
     *
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testRecentlyModifiedContactsAreUnsubscribed()
    {
        $this->setFromTime();

        $contactCollectionMock = $this->createMock(ContactCollection::class);
        $this->contactCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($contactCollectionMock);

        $contactCollectionMock->expects($this->once())
            ->method('getSmsSubscribedContactsWithChangeStatusAtDate')
            ->willReturn($this->getLocalContacts());

        $this->contactResourceMock->expects($this->once())
            ->method('unsubscribeByWebsite')
            ->willReturn(1);

        $this->loggerMock->expects($this->once())
            ->method('info');

        $this->model->processBatch(
            $this->getDotdigitalModifiedContacts(),
            [1, 2]
        );
    }

    public function testContactsAreNotUnsubscribedIfTheyChangedStatusMoreRecently()
    {
        $this->setFromTime();

        $contactCollectionMock = $this->createMock(ContactCollection::class);
        $this->contactCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($contactCollectionMock);

        $contactCollectionMock->expects($this->once())
            ->method('getSmsSubscribedContactsWithChangeStatusAtDate')
            ->willReturn($this->getLocalContactsRecentStatusChange());

        $this->contactResourceMock->expects($this->never())
            ->method('unsubscribeByWebsite')
            ->willReturn(1);

        $this->loggerMock->expects($this->never())
            ->method('info');

        $this->model->processBatch(
            $this->getDotdigitalModifiedContacts(),
            [1, 2]
        );
    }

    public function testContactsAreNotUnsubscribedIfModifiedContactsDontQualify()
    {
        $this->setFromTime();

        $contactCollectionMock = $this->createMock(ContactCollection::class);
        $this->contactCollectionFactoryMock->expects($this->never())
            ->method('create')
            ->willReturn($contactCollectionMock);

        $this->contactResourceMock->expects($this->never())
            ->method('subscribeByWebsite');

        $this->model->processBatch(
            $this->getDotdigitalModifiedContactsNotRelevant(),
            [1, 2]
        );
    }

    public function testContactsAreNotUnsubscribedIfNoLocalMatchesFound()
    {
        $this->setFromTime();

        $contactCollectionMock = $this->createMock(ContactCollection::class);
        $this->contactCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($contactCollectionMock);

        $contactCollectionMock->expects($this->once())
            ->method('getSmsSubscribedContactsWithChangeStatusAtDate')
            ->willReturn([]);

        $this->contactResourceMock->expects($this->never())
            ->method('unsubscribeByWebsite');

        $this->model->processBatch(
            $this->getDotdigitalModifiedContacts(),
            [1, 2]
        );
    }

    private function setFromTime()
    {
        $this->cronFromTimeSetterMock->expects($this->any())
            ->method('getFromTime')
            ->willReturn('2023-06-28T12:00:00+00:00');
    }

    /**
     * 4 recent modified contacts.
     *
     * @return SdkContact[]
     * @throws \Exception
     */
    private function getDotdigitalModifiedContacts()
    {
        $contact1 = new SdkContact();
        $contact1->setIdentifiers(['mobileNumber' => '+447779123456']);
        $contact1->setChannelProperties(['sms' => ['status' => 'unsubscribed']]);

        $contact2 = new SdkContact();
        $contact2->setIdentifiers(['mobileNumber' => '+447779123457']);
        $contact2->setChannelProperties(['sms' => ['status' => 'unsubscribed']]);

        $contact3 = new SdkContact();
        $contact3->setIdentifiers(['mobileNumber' => '+447779123458']);
        $contact3->setChannelProperties(['sms' => ['status' => 'subscribed']]);

        $contact4 = new SdkContact();
        $contact4->setIdentifiers(['mobileNumber' => '+447779123459']);
        $contact4->setChannelProperties(['sms' => ['status' => 'subscribed']]);

        return [$contact1, $contact2, $contact3, $contact4];
    }

    /**
     * 2 recent modified contacts (not subscribed on sms channel).
     *
     * @return SdkContact[]
     * @throws \Exception
     */
    private function getDotdigitalModifiedContactsNotRelevant()
    {
        $contact1 = new SdkContact();
        $contact1->setIdentifiers(['mobileNumber' => '+447779123456']);
        $contact1->setChannelProperties(['sms' => ['status' => 'subscribed']]);

        $contact2 = new SdkContact();
        $contact2->setIdentifiers(['email' => 'chaz@emailsim.io']);
        $contact2->setChannelProperties(['email' => ['status' => 'subscribed']]);

        return [$contact1, $contact2];
    }

    /**
     * 2 local contacts:
     * - one subscribed more recently than the cron started.
     *
     * @return array
     */
    private function getLocalContacts()
    {
        return [[
            'mobile_number' => '+447779123456',
            'sms_change_status_at' => '2023-06-27 12:00:00',
        ], [
            'mobile_number' => '+447779123457',
            'sms_change_status_at' => '2023-06-29 12:00:00',
        ]];
    }

    /**
     * 2 local contacts:
     * - one subscribed more recently than the cron started.
     *
     * @return array
     */
    private function getLocalContactsRecentStatusChange()
    {
        return [[
            'mobile_number' => '+447779123456',
            'sms_change_status_at' => '2023-06-29 12:00:00',
        ], [
            'mobile_number' => '+447779123457',
            'sms_change_status_at' => '2023-06-29 12:00:00',
        ]];
    }
}
