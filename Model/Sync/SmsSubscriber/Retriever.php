<?php

namespace Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber;

use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\CollectionFactory;
use Dotdigitalgroup\Sms\Model\SmsContact;
use Dotdigitalgroup\Sms\Model\Subscriber;
use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Retriever
 *
 * This class is responsible for retrieving SMS subscribers from the database.
 * It provides methods to get a single subscriber or a collection of subscribers.
 */
class Retriever
{
    /**
     * @var int $websiteId The ID of the website for which to retrieve subscribers.
     */
    private $websiteId;

    /**
     * @var CollectionFactory $collectionFactory Factory to create collections of SmsContact objects.
     */
    private $collectionFactory;

    /**
     * @var ResourceConnection $resource Resource connection to execute database queries.
     */
    private $resource;

    /**
     * Retriever constructor.
     *
     * @param CollectionFactory $collectionFactory Factory to create collections of SmsContact objects.
     * @param ResourceConnection $resource Resource connection to execute database queries.
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ResourceConnection $resource
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->resource = $resource;
    }

    /**
     * Set the website ID for which to retrieve subscribers.
     *
     * @param int $websiteId The ID of the website.
     * @return Retriever Returns the current object for method chaining.
     */
    public function setWebsite(int $websiteId):Retriever
    {
        $this->websiteId = $websiteId;
        return $this;
    }

    /**
     * Get a single SMS subscriber by their email contact ID.
     *
     * @param int $email_contact_id The ID of the email contact.
     * @return SmsContact Returns the SmsContact object for the specified email contact ID.
     * @throws Exception Throws an exception if the website ID has not been set.
     */
    public function getSmsSubscriber(int $email_contact_id): SmsContact
    {
        $this->validateObjectState();

        $query = $this->collectionFactory->create();
        $query->addFieldToFilter('main_table.email_contact_id', ['eq' => $email_contact_id]);
        $query->addFieldToFilter('main_table.website_id', ['eq' => $this->websiteId])
            ->addFieldToFilter('mobile_number', ['notnull' => true ])
            ->addFieldToFilter('mobile_number', ['neq' => '']);

        $this->joinOnCollection($query);

        /** @var SmsContact $contact */
        $contact = $query->getFirstItem();
        return $contact;
    }

    /**
     * Get a collection of SMS subscribers.
     *
     * @param int $limit The number of subscribers to retrieve.
     * @param int $offset The number of subscribers to skip before starting to retrieve.
     * @return Collection Returns a collection of SmsContact objects.
     * @throws Exception Throws an exception if the website ID has not been set.
     */
    public function getSmsSubscribers(int $limit, int $offset): Collection
    {
        $this->validateObjectState();

        $query = $this->collectionFactory->create()
            ->addFieldToFilter('main_table.website_id', ['eq' =>  $this->websiteId])
            ->addFieldToFilter('sms_subscriber_status', (string) Subscriber::STATUS_SUBSCRIBED)
            ->addFieldToFilter('sms_subscriber_imported', (string) Contact::EMAIL_CONTACT_NOT_IMPORTED)
            ->addFieldToFilter('mobile_number', ['notnull' => true ])
            ->addFieldToFilter('mobile_number', ['neq' => '']);

        $select = $this->joinOnCollection($query);
        $select->limit($limit, $offset);

        /** @var Collection $collection */
        $collection = $query->load();
        return $collection;
    }

    /**
     * Join additional tables on the collection.
     *
     * @param AbstractCollection $query
     * @return Select
     */
    private function joinOnCollection(AbstractCollection &$query): Select
    {
        return $query
            ->getSelect()
            ->joinLeft(
                ['customer' => $this->getTable('customer_entity')],
                'main_table.customer_id = customer.entity_id',
                [
                    'customer.firstname',
                    'customer.lastname'
                ]
            )
            ->joinLeft(
                ['website' => $this->getTable('store_website')],
                'main_table.website_id = website.website_id',
                [
                    'website.name as website_name'
                ]
            )
            ->joinLeft(
                ['store' => $this->getTable('store')],
                'main_table.store_id = store.store_id',
                [
                    'store.name as store_name',
                ]
            )
            ->joinLeft(
                ['store_group' => $this->getTable('store_group')],
                'main_table.store_id = store_group.group_id',
                [
                    'store_group.name as store_name_additional',
                ]
            );
    }

    /**
     * Get the table name with prefix support.
     *
     * @param string $name The name of the table.
     * @return string Returns the table name with prefix if applicable.
     */
    private function getTable(string $name):string
    {
        return $this->resource->getTableName($name);
    }

    /**
     * Validate the state of the object.
     *
     * @return void
     * @throws Exception Throws an exception if the website ID has not been set.
     */
    private function validateObjectState(): void
    {
        if ($this->websiteId === null) {
            throw new Exception('Website ID must be set before calling this method.'); // @codingStandardsIgnoreLine
        }
    }
}
