<?php

namespace Dotdigitalgroup\Sms\Plugin\Order\Shipment;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Model\Queue\OrderItem\NewShipment;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Shipping\Controller\Adminhtml\Order\Shipment\Save as NewShipmentAction;

class NewShipmentPlugin
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var NewShipment
     */
    private $newShipment;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * NewShipmentPlugin constructor.
     *
     * @param Logger $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param NewShipment $newShipment
     * @param Context $context
     */
    public function __construct(
        Logger $logger,
        OrderRepositoryInterface $orderRepository,
        NewShipment $newShipment,
        Context $context
    ) {
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->newShipment = $newShipment;
        $this->request = $context->getRequest();
    }

    /**
     * After execute.
     *
     * @param NewShipmentAction $subject
     * @param ResultInterface $result
     * @return ResultInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterExecute(
        NewShipmentAction $subject,
        $result
    ) {
        try {
            $order = $this->orderRepository->get(
                $this->request->getParam('order_id')
            );
        } catch (\Exception $e) {
            $order = null;
            $this->logger->debug(
                'Could not load order for shipment',
                [(string) $e]
            );
        }

        $trackings = $this->request->getParam('tracking');

        if ($order && is_array($trackings)) {
            foreach ($trackings as $tracking) {
                $this->newShipment
                    ->buildAdditionalData(
                        $order,
                        $tracking['number'],
                        $tracking['title']
                    )->queue();
            }
        }

        return $result;
    }
}
