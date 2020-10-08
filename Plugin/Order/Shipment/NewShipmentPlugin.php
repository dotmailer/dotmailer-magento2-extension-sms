<?php

namespace Dotdigitalgroup\Sms\Plugin\Order\Shipment;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Shipping\Controller\Adminhtml\Order\Shipment\Save as NewShipmentAction;
use Dotdigitalgroup\Sms\Model\Queue\OrderItem\NewShipment;

class NewShipmentPlugin
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var NewShipment
     */
    private $newShipment;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        NewShipment $newShipment
    ) {
        $this->orderRepository = $orderRepository;
        $this->newShipment = $newShipment;
    }

    /**
     * @param NewShipmentAction $subject
     * @param $result
     */
    public function afterExecute(
        NewShipmentAction $subject,
        $result
    ) {
        $order = $this->orderRepository->get(
            $subject
                ->getRequest()
                ->getParam('order_id')
        );

        $this->newShipment
            ->queue($order);

        return $result;
    }
}
