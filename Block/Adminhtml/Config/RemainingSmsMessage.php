<?php

namespace Dotdigitalgroup\Sms\Block\Adminhtml\Config;

use Dotdigitalgroup\Email\Helper\Data;
use Magento\Framework\Data\Form\Element\AbstractElement;

class RemainingSmsMessage extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @var Data
     */
    private $helper;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    /**
     * @param AbstractElement $element
     * @return string
     * @throws \Exception
     */
    public function render(AbstractElement $element)
    {
        $availableSms = 0;
        $client = $this->helper->getWebsiteApiClient($this->helper->getWebsiteForSelectedScopeInAdmin());
        $accountInfo = $client->getAccountInfo();

        if (!isset($accountInfo->message)) {
            $availableSms = $this->getAvailableSms($accountInfo->properties);
        }

        return sprintf(
            '<div><p>%s</p></div>',
            __('Remaining SMS credits in your account: ' . $availableSms)
        );
    }

    /**
     * @param $properties
     * @return mixed
     */
    private function getAvailableSms($properties)
    {
        foreach ($properties as $property) {
            if ($property->name === "AvailableSmsSendsCredits") {
                return $property->value;
            }
        }
    }
}
