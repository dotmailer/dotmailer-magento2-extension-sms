<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Api;

use Dotdigitalgroup\Sms\Api\Data\SmsMessageInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface SmsMessageRepositoryInterface
{
    /**
     * Save.
     *
     * @param SmsMessageInterface $orderSms
     */
    public function save(SmsMessageInterface $orderSms);

    /**
     * Get by id.
     *
     * @param string|int $id
     * @return SmsMessageInterface
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
