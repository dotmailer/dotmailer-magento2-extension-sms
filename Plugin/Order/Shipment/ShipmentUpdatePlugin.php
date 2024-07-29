<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Plugin\Order\Shipment;

use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Queue\OrderItem\UpdateShipment;
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
     * @var UpdateShipment
     */
    private $updateShipment;

    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * ShipmentUpdatePlugin constructor.
     *
     * @param Configuration $moduleConfig
     * @param OrderRepositoryInterface $orderRepository
     * @param UpdateShipment $updateShipment
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param Context $context
     */
    public function __construct(
        Configuration $moduleConfig,
        OrderRepositoryInterface $orderRepository,
        UpdateShipment $updateShipment,
        ShipmentRepositoryInterface $shipmentRepository,
        Context $context
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->orderRepository = $orderRepository;
        $this->updateShipment = $updateShipment;
        $this->shipmentRepository = $shipmentRepository;
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

        $this->updateShipment
            ->buildAdditionalData(
                $order,
                $this->request->getParam('number'),
                $this->request->getParam('title')
            )->queue();

        return $result;
    }
}
