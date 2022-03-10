<?php

namespace Ablr\Payment\Block\Info;

use Magento\Payment\Block\Info;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\TransactionRepositoryInterface;

/**
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Method extends Info
{
    /**
     * @var string
     */
    protected $_template = 'Ablr_Payment::info/method.phtml';

    /**
     * @var TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * Constructor.
     *
     * @param TransactionRepositoryInterface $transactionRepository
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        TransactionRepositoryInterface $transactionRepository,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->transactionRepository = $transactionRepository;
    }

    /**
     * Render as PDF.
     *
     * @return string
     */
    public function toPdf()
    {
        $this->setTemplate('Ablr_Payment::info/pdf/method.phtml');
        return $this->toHtml();
    }
}
