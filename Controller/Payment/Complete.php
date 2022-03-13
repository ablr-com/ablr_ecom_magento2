<?php

namespace Ablr\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Ablr\Payment\Helper\Api;
use Ablr\Payment\Logger\Logger;
use Ablr\Payment\Helper\Data as Helper;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Complete extends Action
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
     * @var Api
     */
    private $api;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * @param Context                        $context
     * @param PageFactory                    $resultPageFactory
     * @param CheckoutHelper                 $checkoutHelper
     * @param OrderFactory                   $orderFactory
     * @param Api                            $api
     * @param Logger                         $logger
     * @param Helper                         $helper
     * @param InvoiceService                 $invoiceService
     * @param TransactionRepositoryInterface $transactionRepository
     * @param OrderSender                    $orderSender
     * @param InvoiceRepositoryInterface     $invoiceRepository
     * @param OrderRepositoryInterface       $orderRepository
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        CheckoutHelper $checkoutHelper,
        OrderFactory $orderFactory,
        Api $api,
        Logger $logger,
        Helper $helper,
        InvoiceService $invoiceService,
        TransactionRepositoryInterface $transactionRepository,
        OrderSender $orderSender,
        InvoiceRepositoryInterface $invoiceRepository,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);

        $this->checkoutHelper = $checkoutHelper;
        $this->orderFactory = $orderFactory;
        $this->api = $api;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->invoiceService = $invoiceService;
        $this->transactionRepository = $transactionRepository;
        $this->orderSender = $orderSender;
        $this->invoiceRepository = $invoiceRepository;
        $this->orderRepository  = $orderRepository;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $session = $this->checkoutHelper->getCheckout();

        try {
            $checkoutId = $this->getRequest()->getParam('checkout_id');
            if (empty($checkoutId)) {
                throw new LocalizedException(__('Unable to identify payment.'));
            }

            $this->logger->info('Checkout ID: ' . $checkoutId);

            $orderKey = $this->getRequest()->getParam('order_key');
            if (empty($orderKey)) {
                throw new LocalizedException(__('Unable to recognize order.'));
            }

            $this->logger->info('Order Key: ' . $orderKey);

            $incrementId = $this->helper->decryptData($orderKey);
            if (empty($incrementId)) {
                throw new LocalizedException(__('Unable to recognize order.'));
            }

            $this->logger->info('IncrementID: ' . $orderKey);

            $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
            if (!$order->getId()) {
                throw new LocalizedException(__('No order for processing found'));
            }

            // add order information to the session
            $session->setLastOrderId($order->getId())
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastOrderStatus($order->getStatus())
                ->setLastQuoteId($order->getQuoteId())
                ->setLastSuccessQuoteId($order->getQuoteId())
                ->setQuoteId($order->getQuoteId());

            // Get checkout details
            $result = $this->api->getCheckoutDetails($checkoutId, $order->getStoreId());
            $this->logger->info('getCheckoutDetails: ', $result);

            switch ($result['state']) {
                case 'approved':
                    // Payment is success
                    $trxId = $result['code'];
                    $message = __('Order has been paid.');

                    // Change order status
                    $orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
                    $orderStatus = $order->getConfig()->getStateDefaultStatus($orderState);
                    $order->setData('state', $orderState);
                    $order->setStatus($orderStatus);
                    $order->addStatusHistoryComment($message, $orderStatus);

                    // Check Transaction is already registered
                    $trans = $this->transactionRepository->getByTransactionId(
                        $trxId,
                        $order->getPayment()->getId(),
                        $order->getId()
                    );

                    // Register Transaction
                    if (!$trans) {
                        $order->getPayment()->setTransactionId($trxId);

                        $trans = $order->getPayment()->addTransaction(
                            Transaction::TYPE_PAYMENT,
                            null,
                            true
                        );

                        $trans->setIsClosed(0);
                        //$trans->setAdditionalInformation(Transaction::RAW_DETAILS, $transaction);
                        $trans->save();

                        // Set Last Transaction ID
                        $order->getPayment()->setLastTransId($trxId)->save();
                    }

                    $invoice = $this->helper->makeInvoice($order, [], false, '');
                    $invoice->setTransactionId($trxId);
                    $this->invoiceRepository->save($invoice);
                    $this->orderRepository->save($order);

                    // Send order notification
                    try {
                        $this->orderSender->send($order);
                    } catch (\Exception $e) {
                        $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
                    }

                    // Redirect to Success Page
                    $this->checkoutHelper->getCheckout()->getQuote()->setIsActive(false)->save();
                    $this->_redirect('checkout/onepage/success');

                    break;
                default:
                    $message = __('Order has been cancelled.');

                    // Cancel order
                    $order->cancel();
                    $order->addStatusHistoryComment($message);
                    $this->orderRepository->save($order);

                    $this->checkoutHelper->getCheckout()->restoreQuote();
                    $this->messageManager->addError($message);
                    $this->_redirect('checkout/cart');

                    break;
            }
        } catch (\Exception $e) {
            $this->checkoutHelper->getCheckout()->restoreQuote();
            $this->messageManager->addError(__($e->getMessage()));
            $this->_redirect('checkout/cart');
        }
    }
}
