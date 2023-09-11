<?php

namespace Dotdigitalgroup\Sms\Api;

use Dotdigitalgroup\Sms\Api\Data\SmsOrderInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface SmsOrderRepositoryInterface
{
    /**
     * Save.
     *
     * @param SmsOrderInterface $orderSms
     */
    public function save(SmsOrderInterface $orderSms);

    /**
     * Get by id.
     *
     * @param string|int $id
     * @return SmsOrderInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id);

    /**
     * Get Lists.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResults
     */
    public function getList(SearchCriteriaInterface $searchCriteria);
}
