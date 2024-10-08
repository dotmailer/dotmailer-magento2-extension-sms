<?php

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Sync\SmsSubscriber;

use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\SmsContact;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\Exporter;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\WebsiteInterface;
use PHPUnit\Framework\TestCase;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as EmailContactCollection;
use Dotdigital\V3\Models\Contact as DotdigitalContact;

class ExporterTest extends TestCase
{
    /**
     * @var Exporter
     */
    private $exporter;

    /**
     * @var Configuration|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configurationMock;

    /**
     * @var EmailContactCollection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $smsSubscriberCollectionMock;

    /**
     * @var WebsiteInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteMock;

    protected function setUp(): void
    {
        $this->configurationMock = $this->createMock(Configuration::class);
        $this->smsSubscriberCollectionMock = $this->createMock(EmailContactCollection::class);
        $this->websiteMock = $this->createMock(WebsiteInterface::class);
        $this->websiteMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(1);

        $this->exporter = new Exporter(
            $this->configurationMock
        );
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function testExportMethod()
    {
        $emailContact = $this->getMockBuilder(SmsContact::class)
            ->disableOriginalConstructor()
            ->addMethods(['getEmail','getEmailContactId'])
            ->onlyMethods(['getMobileNumber', 'getData'])
            ->getMock();

        $emailContact->expects($this->once())
            ->method('getEmail')
            ->willReturn('test1@example.com');
        $emailContact->expects($this->once())
            ->method('getMobileNumber')
            ->willReturn('5555555555');
        $emailContact->expects($this->once())
            ->method('getData')
            ->willReturn([
                'firstname' => 'Test',
                'lastname' => 'One',
                'store_name' => 'My Store',
                'store_name_additional' => 'My Additional Store',
                'website_name' => 'My Website'
            ]);
        $emailContact->expects($this->once())
            ->method('getEmailContactId')
            ->willReturn(1);

        $this->smsSubscriberCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$emailContact]));

        $this->configurationMock->expects($this->once())
            ->method('getListId')
            ->with($this->websiteMock->getId())
            ->willReturn(123);

        $expectedContact = new DotdigitalContact(['matchIdentifier' => 'email']);
        $expectedContact->setIdentifiers([
            'email' => 'test1@example.com',
            'mobileNumber' => '5555555555'
        ]);
        $expectedContact->setLists([123]);
        $expectedContact->setDataFields([
            'MAPPEDS_FN' => 'Test',
            'lastname' => 'One',
            'store_name' => 'My Store',
            'CHAZ_SN' => 'My Additional Store',
            'website_name' => 'My Website'
        ]);

        $this->exporter->setFieldMapping([
            'firstname' => 'MAPPEDS_FN',
            'lastname' => 'lastname',
            'store_name' => 'store_name',
            'store_name_additional' => 'CHAZ_SN',
            'website_name' => 'website_name'
        ]);

        $exporterBatch = $this->exporter->export(
            $this->smsSubscriberCollectionMock,
            $this->websiteMock
        );

        $this->assertEquals(
            [1 =>$expectedContact],
            $exporterBatch
        );
    }
}
