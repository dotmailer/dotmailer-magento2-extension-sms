<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Plugin\Order\Shipment;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Queue\Publisher\SmsMessagePublisher;
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
     * @var Configuration
     */
    private $moduleConfig;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SmsMessagePublisher
     */
    private $smsMessagePublisher;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param Logger $logger
     * @param Configuration $moduleConfig
     * @param OrderRepositoryInterface $orderRepository
     * @param SmsMessagePublisher $smsMessagePublisher
     * @param Context $context
     */
    public function __construct(
        Logger $logger,
        Configuration $moduleConfig,
        OrderRepositoryInterface $orderRepository,
        SmsMessagePublisher $smsMessagePublisher,
        Context $context
    ) {
        $this->logger = $logger;
        $this->moduleConfig = $moduleConfig;
        $this->orderRepository = $orderRepository;
        $this->smsMessagePublisher = $smsMessagePublisher;
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
            $this->logger->debug(
                'Could not load order for shipment',
                [(string) $e]
            );
            return $result;
        }

        if (!$this->moduleConfig->isTransactionalSmsEnabled($order->getStoreId())) {
            return $result;
        }

        $trackings = $this->request->getParam('tracking');

        if (is_array($trackings)) {
            foreach ($trackings as $tracking) {
                $this->smsMessagePublisher->publish(
                    ConfigInterface::SMS_TYPE_NEW_SHIPMENT,
                    [
                        'order' => $order,
                        'trackingNumber' => $tracking['number'],
                        'trackingCarrier' => $tracking['title']
                    ]
                );
            }
        }

        return $result;
    }
}
