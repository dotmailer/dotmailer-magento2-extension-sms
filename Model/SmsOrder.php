<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model;

use Dotdigitalgroup\Sms\Api\Data\SmsOrderInterface;
use Dotdigitalgroup\Sms\Model\Number\Utility;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsOrder as SmsResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class SmsOrder extends AbstractModel implements SmsOrderInterface
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * @var Utility
     */
    private $numberUtility;

    /**
     * SmsOrder constructor.
     *
     * @param Utility $numberUtility
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Utility $numberUtility,
        Context $context,
        Registry $registry,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->numberUtility = $numberUtility;
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Initialize resource model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(SmsResource::class);
        parent::_construct();
    }

    /**
     * Get id.
     *
     * @return array|mixed|null
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * Get website id.
     *
     * @return int|mixed|null
     */
    public function getWebsiteId()
    {
        return $this->getData(self::WEBSITE_ID);
    }

    /**
     * Get store id.
     *
     * @return int|mixed|null
     */
    public function getStoreId()
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * Get status.
     *
     * @return int|mixed|null
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Get type id.
     *
     * @return int|mixed|null
     */
    public function getTypeId()
    {
        return $this->getData(self::TYPE_ID);
    }

    /**
     * Get order id.
     *
     * @return int|mixed|null
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * Get phone number.
     *
     * @return string|null
     */
    public function getPhoneNumber()
    {
        return $this->numberUtility->returnMobileNumberFromStorage($this->getData(self::PHONE_NUMBER));
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->getData(self::EMAIL);
    }

    /**
     * Get message.
     *
     * @return string|void
     */
    public function getMessage()
    {
        return $this->getData(self::MESSAGE);
    }

    /**
     * Get message id.
     *
     * @return string|void
     */
    public function getMessageId()
    {
        return $this->getData(self::MESSAGE_ID);
    }

    /**
     * Get additional data.
     *
     * @return string|null
     */
    public function getAdditionalData()
    {
        return $this->getData(self::ADDITIONAL_DATA);
    }

    /**
     * Get sent at.
     *
     * @return string|null
     */
    public function getSentAt()
    {
        return $this->getData(self::SENT_AT);
    }

    /**
     * Get created at.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->getData(self::CONTENT);
    }

    /**
     * Set website id.
     *
     * @param int|string $websiteId
     * @return SmsOrder
     */
    public function setWebsiteId($websiteId)
    {
        $this->setData(self::WEBSITE_ID, $websiteId);
        return $this;
    }

    /**
     * Set store id.
     *
     * @param int|string $storeId
     * @return SmsOrder
     */
    public function setStoreId($storeId)
    {
        $this->setData(self::STORE_ID, $storeId);
        return $this;
    }

    /**
     * Set status.
     *
     * @param string $status
     * @return SmsOrder
     */
    public function setStatus($status)
    {
        $this->setData(self::STATUS, $status);
        return $this;
    }

    /**
     * Set type id.
     *
     * @param int|string $typeId
     * @return SmsOrder
     */
    public function setTypeId($typeId)
    {
        $this->setData(self::TYPE_ID, $typeId);
        return $this;
    }

    /**
     * Set order id.
     *
     * @param int|string $orderId
     * @return SmsOrder
     */
    public function setOrderId($orderId)
    {
        $this->setData(self::ORDER_ID, $orderId);
        return $this;
    }

    /**
     * Set phone number.
     *
     * @param int|string $phoneNumber
     * @return SmsOrder
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->setData(
            self::PHONE_NUMBER,
            $this->numberUtility->prepareMobileNumberForStorage($phoneNumber)
        );
        return $this;
    }

    /**
     * Set email.
     *
     * @param string $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->setData(self::EMAIL, $email);
        return $this;
    }

    /**
     * Set message.
     *
     * @param string $message
     * @return SmsOrder
     */
    public function setMessage($message)
    {
        $this->setData(self::MESSAGE, $message);
        return $this;
    }

    /**
     * Set message id.
     *
     * @param int|string $messageId
     * @return SmsOrder
     */
    public function setMessageId($messageId)
    {
        $this->setData(self::MESSAGE_ID, $messageId);
        return $this;
    }

    /**
     * Set additional data.
     *
     * @param array $data
     * @return SmsOrder
     */
    public function setAdditionalData($data)
    {
        $this->setData(self::ADDITIONAL_DATA, $data);
        return $this;
    }

    /**
     * Set sent at.
     *
     * @param mixed $sentAt
     * @return $this|SmsOrder
     */
    public function setSentAt($sentAt)
    {
        $this->setData(self::SENT_AT, $sentAt);
        return $this;
    }

    /**
     * Set content.
     *
     * @param string $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->setData(self::CONTENT, $content);
        return $this;
    }
}
