<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Sync\SmsSubscriber;

use Dotdigitalgroup\Email\Model\Customer\RegistrationStoreResolver;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\Collection;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\CollectionFactory;
use Dotdigitalgroup\Sms\Model\SmsContact;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\Retriever;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Platform\Quote;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Select\ColumnsRenderer;
use Magento\Framework\DB\Select\FromRenderer;
use Magento\Framework\DB\Select\LimitRenderer;
use Magento\Framework\DB\Select\SelectRenderer;
use Magento\Framework\DB\Select\WhereRenderer;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class RetrieverTest extends TestCase
{
    /**
     * @var \PDO
     */
    private $database;

    protected function setUp(): void
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('PDO SQLite is required to exercise the subscriber selection SQL.');
        }
        $this->database = new \PDO('sqlite::memory:');
        $this->database->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->database->exec('CREATE TABLE pref_customer_entity (
            entity_id INTEGER, store_id INTEGER, firstname TEXT, lastname TEXT
        )');
        $this->database->exec('CREATE TABLE pref_store (
            store_id INTEGER, website_id INTEGER, name TEXT, group_id INTEGER
        )');
        $this->database->exec('CREATE TABLE pref_store_website (website_id INTEGER, name TEXT)');
        $this->database->exec('CREATE TABLE pref_store_group (group_id INTEGER, name TEXT)');
        $this->database->exec('CREATE TABLE pref_email_contact (
            email_contact_id INTEGER, customer_id INTEGER, website_id INTEGER, store_id INTEGER,
            sms_subscriber_status INTEGER, sms_subscriber_imported INTEGER, mobile_number TEXT
        )');
        $this->database->exec("INSERT INTO pref_customer_entity VALUES
            (10, 2, 'Registered', 'Customer'), (11, 9, 'Shared', 'Customer'), (12, NULL, 'No', 'Store')");
        $this->database->exec("INSERT INTO pref_store VALUES
            (1, 1, 'Default', 1), (2, 1, 'Override', 3), (9, 2, 'Other', 2)");
        $this->database->exec("INSERT INTO pref_store_group VALUES
            (1, 'Group One'), (2, 'Group Two'), (3, 'Group Three')");
        $this->database->exec("INSERT INTO pref_store_website VALUES (1, 'Website'), (2, 'Other website')");
        $this->database->exec("INSERT INTO pref_email_contact VALUES
            (1, 10, 1, 1, 1, 0, '447100000001'),
            (2, 0, 1, 2, 1, 0, '447100000002'),
            (3, 11, 1, 2, 1, 0, '447100000003'),
            (4, 12, 1, 2, 1, 0, '447100000004'),
            (5, 0, 1, 9, 1, 0, '447100000005'),
            (6, 0, 1, 0, 1, 0, '447100000006'),
            (7, 0, 2, 2, 1, 0, '447100000007'),
            (8, 0, 1, 2, 2, 0, '447100000008'),
            (9, 0, 1, 2, 1, 1, '447100000009'),
            (10, 0, 1, 2, 1, 0, ''),
            (11, 0, 1, 2, 1, 0, NULL),
            (12, 999, 1, 2, 1, 0, '447100000012')");
        $this->database->exec('ALTER TABLE pref_email_contact ADD COLUMN email TEXT');
        $this->database->exec(
            "UPDATE pref_email_contact SET email = 'contact' || email_contact_id || '@example.com'"
        );
        $this->database->exec(
            "UPDATE pref_email_contact SET email = 'contact2@example.com' WHERE email_contact_id = 7"
        );
    }

    public function testBatchUsesRegistrationOrFirstContactStoreAndPreservesEligibility(): void
    {
        $items = $this->retriever()->getSmsSubscribers(100, 0, [2])->getItems();

        $this->assertSame([1, 2], array_column($items, 'email_contact_id'));
        $this->assertSame([2, 2], array_column($items, 'sms_sync_store_id'));
        $this->assertSame([1, 2], array_column($items, 'store_id'));
    }

    public function testStoreNameAdditionalIsTheGroupOwningTheStoreViewNotTheGroupMatchingItsId(): void
    {
        $batch = $this->retriever()->getSmsSubscribers(100, 0, [2])->getItems();
        $this->assertSame(['Group One', 'Group Three'], array_column($batch, 'store_name_additional'));

        $this->assertSame('Group Three', $this->retriever()->getSmsSubscriber(2)->getData('store_name_additional'));
    }

    public function testMissingOrCrossWebsiteStoresJoinOnlyTheWebsiteFallbackCohort(): void
    {
        $items = $this->retriever()->getSmsSubscribers(100, 0, [1])->getItems();

        $this->assertSame([3, 4, 5, 6, 12], array_column($items, 'email_contact_id'));
        $this->assertSame([1, 1, 1, 1, 1], array_column($items, 'sms_sync_store_id'));
    }

    public function testDirectLookupUsesTheSameStoresIncludingUnsubscribedOrImportedContacts(): void
    {
        foreach ([1 => 2, 2 => 2, 3 => 1, 5 => 1, 8 => 2, 9 => 2] as $contactId => $storeId) {
            $contact = $this->retriever()->getSmsSubscriber($contactId);
            $this->assertSame($storeId, $contact->getSmsSyncStoreId());
        }
    }

    public function testOtherWebsiteOrMissingContactCannotSilentlyUseWebsiteList(): void
    {
        $contact = $this->retriever()->getSmsSubscriber(7);

        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $contact->getSmsSyncStoreId();
    }

    public function testEmailOnlyUnsubscribeUsesTheSameStoreAfterMobileRemovalWithoutCrossingWebsites(): void
    {
        foreach ([1 => 2, 2 => 2, 3 => 1, 8 => 2, 9 => 2, 10 => 2, 11 => 2] as $contactId => $storeId) {
            $contact = $this->retriever()->getSmsSubscriberByEmail("contact$contactId@example.com");
            $this->assertSame($contactId, (int) $contact->getData('email_contact_id'));
            $this->assertSame($storeId, $contact->getSmsSyncStoreId());
        }
    }

    /**
     * Exercise the actual selection SQL without a Magento database.
     *
     * @return Retriever
     */
    private function retriever(): Retriever
    {
        $adapter = $this->getMockBuilder(Mysql::class)->disableOriginalConstructor()
            ->onlyMethods(['quoteInto'])->getMock();
        $adapter->method('quoteInto')->willReturnCallback(function ($sql, $value) {
            $values = array_map(function ($item) {
                return is_int($item) ? (string) $item : $this->database->quote((string) $item);
            }, is_array($value) ? $value : [$value]);
            return str_replace('?', implode(', ', $values), $sql);
        });
        $quote = new Quote();
        $renderer = new SelectRenderer([
            ['part' => Select::COLUMNS, 'sort' => 10, 'renderer' => new ColumnsRenderer($quote)],
            ['part' => Select::FROM, 'sort' => 20, 'renderer' => new FromRenderer($quote)],
            ['part' => Select::WHERE, 'sort' => 30, 'renderer' => new WhereRenderer()],
            ['part' => Select::LIMIT_COUNT, 'sort' => 40, 'renderer' => new LimitRenderer()]
        ]);
        $select = (new Select($adapter, $renderer))->from(['main_table' => 'pref_email_contact']);
        $collection = $this->createMock(Collection::class);
        $collection->method('getSelect')->willReturn($select);
        $collection->method('addFieldToFilter')->willReturnCallback(
            function ($field, $condition) use ($adapter, $select, $collection) {
                $select->where($adapter->prepareSqlCondition($field, $condition));
                return $collection;
            }
        );
        $collection->method('load')->willReturnSelf();
        $collection->method('getItems')->willReturnCallback(function () use ($select) {
            return $this->database->query((string) $select)->fetchAll(\PDO::FETCH_ASSOC);
        });
        $collection->method('getFirstItem')->willReturnCallback(function () use ($select) {
            $data = $this->database->query((string) $select)->fetch(\PDO::FETCH_ASSOC);
            $contact = $this->getMockBuilder(SmsContact::class)->disableOriginalConstructor()
                ->onlyMethods([])->getMock();
            return $contact->setData($data ?: []);
        });
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        $resource = $this->createStub(ResourceConnection::class);
        $resource->method('getTableName')->willReturnCallback(function ($table) {
            return 'pref_' . $table;
        });
        $storeResolver = $this->createStub(RegistrationStoreResolver::class);
        $storeResolver->method('getFallbackStoreId')->willReturn(1);

        return (new Retriever($factory, $resource, $storeResolver))->setWebsite(1);
    }
}
