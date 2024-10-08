<?php

namespace Dotdigitalgroup\Sms\Test\Unit\Wrappers;

use Dotdigitalgroup\Email\Model\Apiconnector\V3\Client as V3Client;

class V3ClientWrapper
{
    /**
     * @var V3Client
     */
    private $v3Client;

    /**
     * V3ClientWrapper constructor.
     *
     * @param V3Client $v3Client
     */
    public function __construct(V3Client $v3Client)
    {
        $this->v3Client = $v3Client;
    }

    /**
     * Patch by identifier.
     *
     * @param $arg1
     * @param $arg2
     * @param $arg3
     * @return mixed
     */
    public function patchByIdentifier($arg1, $arg2, $arg3)
    {
        return $this->v3Client->patchByIdentifier($arg1, $arg2, $arg3);
    }
}
