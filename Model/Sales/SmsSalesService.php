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
     *
     */
    public function setIsOrderSaveAfterExecuted()
    {
        $this->orderSaveAfterExecuted = true;
    }

    /**
     * @return bool
     */
    public function isOrderSaveAfterExecuted()
    {
        return $this->orderSaveAfterExecuted;
    }

    /**
     *
     */
    public function setIsOrderCreditmemoSaveAfterExecuted()
    {
        $this->orderCreditmemoSaveAfterExecuted = true;
    }

    /**
     * @return bool
     */
    public function isOrderCreditmemoSaveAfterExecuted()
    {
        return $this->orderCreditmemoSaveAfterExecuted;
    }
}
