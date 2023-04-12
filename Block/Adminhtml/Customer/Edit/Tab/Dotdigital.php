<?php

namespace Dotdigitalgroup\Sms\Block\Adminhtml\Customer\Edit\Tab;

use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Sms\Component\Consent\ConsentCheckbox;
use Dotdigitalgroup\Sms\Component\Consent\ConsentTelephone;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Subscriber;
use Magento\Backend\Block\Template\Context;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Store\Model\System\Store;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Store\Model\StoreManagerInterface;

class Dotdigital extends Generic implements TabInterface
{
    /**
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var ConsentCheckbox
     */
    private $consentCheckbox;

    /**
     * @var ConsentTelephone
     */
    private $consentTelephone;

    /**
     * @var Configuration
     */
    private $moduleConfig;

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Store $systemStore
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param ConsentCheckbox $consentCheckbox
     * @param ConsentTelephone $consentTelephone
     * @param Configuration $moduleConfig
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        ContactCollectionFactory $contactCollectionFactory,
        ConsentCheckbox $consentCheckbox,
        ConsentTelephone $consentTelephone,
        Configuration $moduleConfig,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_systemStore = $systemStore;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->consentCheckbox = $consentCheckbox;
        $this->consentTelephone = $consentTelephone;
        $this->moduleConfig = $moduleConfig;
        $this->storeManager = $storeManager;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Get customer id
     *
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * Get tab label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Dotdigital');
    }

    /**
     * Get tab title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Dotdigital');
    }

    /**
     * Can show tab
     *
     * @return bool
     */
    public function canShowTab()
    {
        if ($this->getCustomerId()) {
            return true;
        }
        return false;
    }

    /**
     * Tab is hidden
     *
     * @return bool
     */
    public function isHidden()
    {
        if ($this->getCustomerId()) {
            return false;
        }
        return true;
    }

    /**
     * Get tab class
     *
     * @return string
     */
    public function getTabClass()
    {
        return '';
    }

    /**
     * Return URL link to tab content
     *
     * @return string
     */
    public function getTabUrl()
    {
        return '';
    }

    /**
     * Determines if tab should be loaded via Ajax call
     *
     * @return bool
     */
    public function isAjaxLoaded()
    {
        return false;
    }

    /**
     * Create form
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initForm()
    {
        if (!$this->canShowTab()) {
            return $this;
        }

        $smsSubscriberData = $this->retrieveSmsSubscriberData();

        $form = $this->_formFactory->create();
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('SMS Marketing Subscription')]
        );

        $checkbox = $fieldset->addField(
            'dd_sms_consent_checkbox',
            'checkbox',
            [
                'label' => __('Subscribed to SMS marketing'),
                'data-form-part' => $this->getData('target_form'),
                'value' => $smsSubscriberData['is_subscribed'],
                'onchange' => 'this.value = this.checked;'
            ]
        );
        $checkbox->setIsChecked($smsSubscriberData['is_subscribed']);

        $mobileNumber = $fieldset->addField(
            'dd_sms_consent_mobile_number',
            'text',
            [
                'component' => 'Magento_Ui/js/form/element/abstract',
                'name' => 'mobile_number',
                'label' => __('SMS mobile number'),
                'data-form-part' => $this->getData('target_form'),
                'value' => $smsSubscriberData['mobile_number'],
                'config' => [
                    'template' => 'ui/form/field',
                    'elementTmpl' => 'Dotdigitalgroup_Sms/form/element/telephone'
                ],
                'validation' => [
                    "max_text_length" => 255,
                    "min_text_length" => 1,
                    'validate-phone-number' => true
                ],
            ]
        );

        $this->setForm($form);
        return $this;
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->canShowTab()) {
            $this->initForm();
            return parent::_toHtml();
        } else {
            return '';
        }
    }

    /**
     * Prepare the layout.
     *
     * @return $this
     */
    public function getFormHtml()
    {
        $html = parent::getFormHtml();
        // You can call other Block also by using this function if you want to add phtml file. Otherwise, you can remove it.
//        $html .= $this->getLayout()->createBlock(
//            'Dotdigitalgroup_Sms\CustomerEdit\Block\Adminhtml\Customer\Edit\Tab\AdditionalBlock'
//        )->toHtml();
        return $html;
    }

    /**
     * Retrieve SMS subscriber data
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function retrieveSmsSubscriberData()
    {
        $websiteId = $this->storeManager->getWebsite()->getId();
        $contact = $this->contactCollectionFactory->create()
            ->loadByCustomerIdAndWebsiteId(
                $this->getCustomerId(),
                $websiteId
            );

        return [
            'is_subscribed' => $contact && $contact->getSmsSubscriberStatus() == Subscriber::STATUS_SUBSCRIBED,
            'mobile_number' => $contact ? $contact->getMobileNumber() : ''
        ];
    }
}
