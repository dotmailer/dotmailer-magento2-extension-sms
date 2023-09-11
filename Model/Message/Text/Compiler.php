<?php

namespace Dotdigitalgroup\Sms\Model\Message\Text;

use Dotdigitalgroup\Sms\Api\Data\SmsOrderInterface;
use Dotdigitalgroup\Sms\Model\Message\Variable\Resolver;

class Compiler
{
    /**
     * @var Resolver
     */
    private $variableResolver;

    /**
     * @param Resolver $variableResolver
     */
    public function __construct(
        Resolver $variableResolver
    ) {
        $this->variableResolver = $variableResolver;
    }

    /**
     * Compile text with variables
     *
     * @param string $text
     * @param SmsOrderInterface $sms
     * @return string
     */
    public function compile($text, $sms)
    {
        if (preg_match_all($this->getRegularExpression(), $text, $matches)) {
            $matchesToReplace = $matches[0];
            $matchesToResolve = $matches[1];

            foreach ($matchesToResolve as $i => $match) {
                $replacedValue = $this->variableResolver->resolve(trim($match, " "), $sms);
                $text = str_replace($matchesToReplace[$i], $replacedValue, $text);
            }
        }

        return $text;
    }

    /**
     * Get regular expression for matching variables.
     *
     * @return string
     */
    private function getRegularExpression(): string
    {
        return '/{{([^}]+)\}}/si';
    }
}
