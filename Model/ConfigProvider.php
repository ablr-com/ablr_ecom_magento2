<?php

namespace Ablr\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Model\Context;
use Magento\Payment\Helper\Data as PaymentHelper;
use Ablr\Payment\Model\Method;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @param Context $context
     * @param PaymentHelper $paymentHelper
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        Context $context,
        PaymentHelper $paymentHelper
    ) {
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $redirectUrl = null;

        try {
            /** @var Method $method */
            $method = $this->paymentHelper->getMethodInstance(Method::METHOD_CODE);
            if ($method->isAvailable()) {
                $redirectUrl = $method->getCheckoutRedirectUrl();
            }
        } catch (\Exception $e) {
            //
        }

        return [
            'payment' => [
                Method::METHOD_CODE => [
                    'redirect_url' => $redirectUrl
                ],
            ],
        ];
    }
}
