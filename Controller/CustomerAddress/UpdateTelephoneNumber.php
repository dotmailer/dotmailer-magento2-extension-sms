<?php

namespace Dotdigitalgroup\Sms\Controller\CustomerAddress;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Customer\Api\AddressRepositoryInterface;

class UpdateTelephoneNumber extends \Magento\Framework\App\Action\Action
{
    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * UpdatePhoneNumber constructor.
     *
     * @param Context $context
     * @param AddressRepositoryInterface $addressRepository
     */
    public function __construct(
        Context $context,
        AddressRepositoryInterface $addressRepository
    ) {
        $this->addressRepository = $addressRepository;
        parent::__construct($context);
    }

    /**
     * Execute action.
     *
     * @return ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $addressId = $this->getRequest()->getParam('addressId');
        $phoneNumber = $this->getRequest()->getParam('phoneNumber');

        $address = $this->addressRepository->getById($addressId);
        $address->setTelephone($phoneNumber);
        $this->addressRepository->save($address);
    }
}
