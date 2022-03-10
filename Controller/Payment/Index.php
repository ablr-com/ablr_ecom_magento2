<?php

namespace Ablr\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Ablr\Payment\Helper\Api;
use Ablr\Payment\Logger\Logger;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var CheckoutHelper
     */
    private $checkoutHelper;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Api
     */
    private $api;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        CheckoutHelper $checkoutHelper,
        OrderFactory $orderFactory,
        OrderRepositoryInterface $orderRepository,
        Api $api,
        Logger $logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);

        $this->checkoutHelper = $checkoutHelper;
        $this->orderFactory = $orderFactory;
        $this->api = $api;
        $this->logger = $logger;
        $this->orderRepository  = $orderRepository;
    }

    public function execute()
    {
        //https://office.frozen.zone/magento2/ablr/payment/index/

        $session = $this->checkoutHelper->getCheckout();

        try {
            // Load Order
            $incrementId = $session->getLastRealOrderId();
            $this->logger->info('IncrementID: ' . $incrementId);

            $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
            if (!$order->getId()) {
                throw new LocalizedException(__('No order for processing found'));
            }

            $result = $this->api->initiateCheckout($order);
            $this->logger->info('initiateCheckout: ', $result);

            // Save ID
            $order->setAblrID($result['id']);
            $this->orderRepository->save($order);

            // Redirect
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($result['checkout_url']);

            return $resultRedirect;
        } catch (\Exception $e) {
            $this->checkoutHelper->getCheckout()->restoreQuote();
            $this->messageManager->addError(__($e->getMessage()));
            $this->_redirect('checkout/cart');
        }
    }
}
