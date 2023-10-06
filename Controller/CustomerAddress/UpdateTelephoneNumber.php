<?php

namespace Dotdigitalgroup\Sms\Controller\CustomerAddress;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\ResultFactory;

class UpdateTelephoneNumber implements HttpPostActionInterface
{
    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * UpdatePhoneNumber constructor.
     *
     * @param Context $context
     * @param AddressRepositoryInterface $addressRepository
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        Context $context,
        AddressRepositoryInterface $addressRepository,
        ResultFactory $resultFactory
    ) {
        $this->addressRepository = $addressRepository;
        $this->request = $context->getRequest();
        $this->resultFactory = $resultFactory;
    }

    /**
     * Execute action.
     *
     * @return ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $addressId = $this->request->getParam('addressId');
        $phoneNumber = $this->request->getParam('phoneNumber');

        $address = $this->addressRepository->getById($addressId);
        $address->setTelephone($phoneNumber);
        $this->addressRepository->save($address);

        return $this->resultFactory->create(ResultFactory::TYPE_RAW)
            ->setHttpResponseCode(200);
    }
}
