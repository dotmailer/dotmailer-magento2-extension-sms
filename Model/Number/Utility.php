<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Number;

class Utility
{
    /**
     * Prepare mobile number for storage.
     *
     * @param string|null $number
     *
     * @return int
     */
    public function prepareMobileNumberForStorage(?string $number)
    {
        return (int) str_replace(' ', '', $number);
    }

    /**
     * Return mobile number from storage.
     *
     * @param string|null $number
     *
     * @return string
     */
    public function returnMobileNumberFromStorage(?string $number)
    {
        return '+' . (int) $number;
    }
}
