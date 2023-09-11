<?php

namespace Dotdigitalgroup\Sms\Model\Sales;

class SmsSalesService
{
    /**
     * @var bool
     */
    private $orderSaveAfterExecuted = false;

    /**
     * @var bool
     */
    private $orderCreditmemoSaveAfterExecuted = false;

    /**
     * Set flag to indicate that order save after event has been executed
     *
     * @return void
     */
    public function setIsOrderSaveAfterExecuted()
    {
        $this->orderSaveAfterExecuted = true;
    }

    /**
     * Check if order save after event has been executed.
     *
     * @return bool
     */
    public function isOrderSaveAfterExecuted()
    {
        return $this->orderSaveAfterExecuted;
    }

    /**
     * Set flag to indicate that order credit memo save after event has been executed
     *
     * @return void
     */
    public function setIsOrderCreditmemoSaveAfterExecuted()
    {
        $this->orderCreditmemoSaveAfterExecuted = true;
    }

    /**
     * Check if order credit memo save after event has been executed.
     *
     * @return bool
     */
    public function isOrderCreditmemoSaveAfterExecuted()
    {
        return $this->orderCreditmemoSaveAfterExecuted;
    }
}
