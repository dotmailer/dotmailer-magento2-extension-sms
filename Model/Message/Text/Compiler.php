<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Message\Text;

use Dotdigitalgroup\Sms\Api\Data\SmsOrderInterface;
use Dotdigitalgroup\Sms\Model\Message\Variable\ResolverInterface;
use Magento\Framework\Exception\LocalizedException;

class Compiler
{
    /**
     * Resolvers are defined in di.xml
     * @var array<ResolverInterface>
     */
    private $resolvers = [];

    /**
     * Compiler constructor.
     *
     * @param array $resolvers
     */
    public function __construct(array $resolvers = [])
    {
        $this->setResolvers($resolvers);
    }

    /**
     * Compile text with variables
     *
     * @param string $text
     * @param SmsOrderInterface $sms
     *
     * @return string
     * @throws LocalizedException
     */
    public function compile($text, $sms)
    {
        if (preg_match_all($this->getRegularExpression(), $text, $matches)) {
            $matchesToReplace = $matches[0];
            $matchesToResolve = $matches[1];

            if (!isset($this->resolvers[$sms->getTypeId()])) {
                throw new LocalizedException(__(
                    sprintf(
                        'Could not find a resolver for type id %s. Check di.xml and/or re-run setup:di:compile.',
                        $sms->getTypeId()
                    )
                ));
            }

            foreach ($matchesToResolve as $i => $match) {
                $replacedValue = $this->resolvers[$sms->getTypeId()]->resolve(trim($match, " "), $sms);
                $text = str_replace($matchesToReplace[$i], $replacedValue, $text);
            }
        }

        return $text;
    }

    /**
     * Get regular expression for matching variables.
     *
     * The pattern will find any string inside {{ brackets }}.
     *
     * @return string
     */
    private function getRegularExpression(): string
    {
        return '/{{([^}]+)\}}/si';
    }

    /**
     * Set resolvers by type id.
     *
     * @param array $resolvers
     *
     * @return void
     */
    public function setResolvers($resolvers)
    {
        $this->resolvers = $resolvers;
    }
}
