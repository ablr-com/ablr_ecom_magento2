<?php

namespace Ablr\Payment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\App\DeploymentConfig;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Helper\Data as SalesData;
use Magento\Sales\Model\Order\Invoice;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Data extends AbstractHelper
{
    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var SalesData
     */
    private $salesData;

    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * @var DeploymentConfig
     */
    private $depConfig;

    public function __construct(
        Context $context,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        TransactionFactory $transactionFactory,
        SalesData $salesData,
        InvoiceRepositoryInterface $invoiceRepository,
        DeploymentConfig $depConfig
    ) {
        parent::__construct($context);

        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
        $this->transactionFactory = $transactionFactory;
        $this->salesData = $salesData;
        $this->invoiceRepository = $invoiceRepository;
        $this->depConfig = $depConfig;
    }

    /**
     * Create Invoice.
     *
     * @param OrderInterface $order $order
     * @param array $qtys
     * @param bool $online
     * @param string $comment
     * @return Invoice
     */
    public function makeInvoice(OrderInterface $order, array $qtys, $online, $comment)
    {
        /** @var Invoice $invoice */
        $invoice = $this->invoiceService->prepareInvoice($order, $qtys);
        $invoice->setRequestedCaptureCase($online ? Invoice::CAPTURE_ONLINE : Invoice::CAPTURE_OFFLINE);

        // Add Comment
        if (!empty($comment)) {
            $invoice->addComment(
                $comment,
                true,
                true
            );

            $invoice->setCustomerNote($comment);
            $invoice->setCustomerNoteNotify(true);
        }

        $invoice->register();
        $invoice->getOrder()->setIsInProcess(true);
        $invoice->setIsPaid(true);

        $dbTransaction = $this->transactionFactory->create();
        $dbTransaction->addObject($invoice)
                      ->addObject($invoice->getOrder())
                      ->save();

        // send invoice emails
        if ($this->salesData->canSendNewInvoiceEmail()) {
            try {
                $this->invoiceSender->send($invoice);
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
        }

        // Assign Last Transaction Id with Invoice
        $transactionId = $invoice->getOrder()->getPayment()->getLastTransId();
        if ($transactionId) {
            $invoice->setTransactionId($transactionId);
            $this->invoiceRepository->save($invoice);
        }

        return $invoice;
    }

    /**
     * Encrypt.
     *
     * @param string $data
     *
     * @return false|string
     */
    public function encryptData($data)
    {
        return base64_encode(openssl_encrypt(
            $data,
            'AES-128-ECB',
            hash('sha256', $this->getEncryptionKey())
        ));
    }

    /**
     * Decrypt.
     *
     * @param string $data
     *
     * @return false|string
     */
    public function decryptData($data)
    {
        return openssl_decrypt(
            base64_decode($data),
            'AES-128-ECB',
            hash('sha256', $this->getEncryptionKey())
        );
    }

    /**
     * Get Encryption key.
     *
     * @return string
     */
    private function getEncryptionKey()
    {
        return $this->depConfig->get('crypt/key');
    }
}
