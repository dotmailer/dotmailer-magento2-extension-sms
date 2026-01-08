<?php

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Message\Text;

use Dotdigitalgroup\Sms\Model\Message\Text\Compiler;
use Dotdigitalgroup\Sms\Model\Message\Variable\Resolver;
use Dotdigitalgroup\Sms\Model\SmsMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompilerTest extends TestCase
{
    /**
     * @var SmsMessage|MockObject
     */
    private $smsMessageMock;

    /**
     * @var Resolver|MockObject
     */
    private $variableResolverMock;

    /**
     * @var Compiler|MockObject
     */
    private $compiler;

    protected function setUp() :void
    {
        $this->variableResolverMock = $this->createMock(Resolver::class);
        $this->smsMessageMock = $this->createMock(SmsMessage::class);

        $this->compiler = new Compiler([
            "1" => $this->variableResolverMock
        ]);
    }

    public function testThatMatchesAreIdentified()
    {
        $rawText = $this->getRawText();

        $this->smsMessageMock->expects($this->exactly(6))
            ->method('getTypeId')
            ->willReturn("1");

        $this->variableResolverMock->expects($this->exactly(5))
            ->method('resolve')
            ->willReturnOnConsecutiveCalls(
                'Chaz',
                'Kangeroo',
                '1',
                'Default Store View',
                'processing'
            );

        $compiledText = $this->compiler->compile($rawText, $this->smsMessageMock);
        $this->assertEquals($compiledText, $this->getTargetText());
    }

    private function getRawText()
    {
        // @codingStandardsIgnoreLine
        return "Thanks {{first_name}} {{last_name}}, your order {{order_id}} has been placed on {{store_name}} and is now {{order_status}}. We'll notify you when it ships.";
    }

    private function getTargetText()
    {
        // @codingStandardsIgnoreLine
        return "Thanks Chaz Kangeroo, your order 1 has been placed on Default Store View and is now processing. We'll notify you when it ships.";
    }
}
