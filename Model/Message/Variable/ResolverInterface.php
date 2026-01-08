<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Message\Variable;

use Dotdigitalgroup\Sms\Api\Data\SmsMessageInterface;

interface ResolverInterface
{
    /**
     * Resolve variable from SMS template.
     *
     * @param string $variable
     * @param SmsMessageInterface $sms
     * @return mixed|null
     */
    public function resolve(string $variable, SmsMessageInterface $sms);
}
