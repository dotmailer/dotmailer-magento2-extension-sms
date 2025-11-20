<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Plugin\Order\Shipment;

use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Queue\Publisher\SmsMessagePublisher;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Shipping\Controller\Adminhtml\Order\Shipment\AddTrack as UpdateShipmentAction;

class ShipmentUpdatePlugin
{
    /**
     * @var Configuration
     */
    private $moduleConfig;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var SmsMessagePublisher
     */
    private $smsMessagePublisher;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * ShipmentUpdatePlugin constructor.
     *
     * @param Configuration $moduleConfig
     * @param OrderRepositoryInterface $orderRepository
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param SmsMessagePublisher $smsMessagePublisher
     * @param Context $context
     */
    public function __construct(
        Configuration $moduleConfig,
        OrderRepositoryInterface $orderRepository,
        ShipmentRepositoryInterface $shipmentRepository,
        SmsMessagePublisher $smsMessagePublisher,
        Context $context
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->orderRepository = $orderRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->smsMessagePublisher = $smsMessagePublisher;
        $this->request = $context->getRequest();
    }

    /**
     * After execute.
     *
     * @param UpdateShipmentAction $subject
     * @param ResultInterface $result
     * @return ResultInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterExecute(
        UpdateShipmentAction $subject,
        $result
    ) {
        $shipment = $this->shipmentRepository->get(
            $this->request->getParam('shipment_id')
        );

        if (!$this->moduleConfig->isTransactionalSmsEnabled($shipment->getStoreId())) {
            return $result;
        }

        $orderId = $shipment->getOrderId();

        $order = $this->orderRepository->get(
            $orderId
        );

        $this->smsMessagePublisher->publish(
            ConfigInterface::SMS_TYPE_UPDATE_SHIPMENT,
            [
                'order' => $order,
                'trackingNumber' => $this->request->getParam('number'),
                'trackingCarrier' => $this->request->getParam('title')
            ]
        );

        return $result;
    }
}
