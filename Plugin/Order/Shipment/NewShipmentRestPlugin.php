<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Plugin\Order\Shipment;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Queue\Publisher\SmsMessagePublisher;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class NewShipmentRestPlugin
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var State
     */
    private $state;

    /**
     * @var LoggerInterface
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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Data $helper
     * @param State $state
     * @param LoggerInterface $logger
     * @param Configuration $moduleConfig
     * @param OrderRepositoryInterface $orderRepository
     * @param SmsMessagePublisher $smsMessagePublisher
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Data $helper,
        State $state,
        LoggerInterface $logger,
        Configuration $moduleConfig,
        OrderRepositoryInterface $orderRepository,
        SmsMessagePublisher $smsMessagePublisher,
        StoreManagerInterface $storeManager
    ) {
        $this->helper = $helper;
        $this->state = $state;
        $this->logger = $logger;
        $this->moduleConfig = $moduleConfig;
        $this->orderRepository = $orderRepository;
        $this->smsMessagePublisher = $smsMessagePublisher;
        $this->storeManager = $storeManager;
    }

    /**
     * After execute.
     *
     * @param ShipOrderInterface $subject
     * @param int $result
     * @param int $orderId
     * @param \Magento\Sales\Api\Data\ShipmentItemCreationInterface[] $items
     * @param bool $notify
     * @param bool $appendComment
     * @param \Magento\Sales\Api\Data\ShipmentCommentCreationInterface|null $comment
     * @param \Magento\Sales\Api\Data\ShipmentTrackCreationInterface[] $tracks
     * @return string|int
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterExecute(
        ShipOrderInterface $subject,
        $result,
        $orderId,
        array $items = [],
        $notify = false,
        $appendComment = false,
        ?\Magento\Sales\Api\Data\ShipmentCommentCreationInterface $comment = null,
        array $tracks = []
    ) {
        try {
            $order = $this->orderRepository->get($orderId);
            $websiteId = $this->storeManager->getStore($order->getStoreId())->getWebsiteId();
            if (!$this->helper->isEnabled($websiteId) &&
                $this->state->getAreaCode() !== \Magento\Framework\App\Area::AREA_WEBAPI_REST) {
                return $result;
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Could not load order for shipment',
                [(string) $e]
            );
            return $result;
        }

        if (!$this->moduleConfig->isTransactionalSmsEnabled($order->getStoreId())) {
            return $result;
        }

        if (is_array($tracks)) {
            foreach ($tracks as $tracking) {
                if ($tracking instanceof \Magento\Sales\Api\Data\ShipmentTrackCreationInterface) {
                    $this->smsMessagePublisher->publish(
                        ConfigInterface::SMS_TYPE_NEW_SHIPMENT,
                        [
                            'order' => $order,
                            'trackingNumber' => $tracking->getTrackNumber(),
                            'trackingCarrier' => $tracking->getTitle()
                        ]
                    );
                }
            }
        }

        return $result;
    }
}
