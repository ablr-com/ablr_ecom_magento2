<?php

namespace Ablr\Payment\Helper;

use Ablr\Payment\Helper\Data as Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Ablr\Payment\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Ablr\Payment\Logger\Logger;

class Api extends AbstractHelper
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Helper
     */
    private $helper;

    public function __construct(
        Context $context,
        Config $config,
        Curl $curl,
        Json $json,
        Logger $logger,
        UrlInterface $urlBuilder,
        Helper $helper
    ) {
        $this->config = $config;
        $this->curl = $curl;
        $this->json = $json;
        $this->logger = $logger;
        $this->urlBuilder = $urlBuilder;
        $this->helper = $helper;

        parent::__construct($context);
    }

    /**
     * Initiate checkout.
     *
     * @param OrderInterface $order
     *
     * @return array{id: string, checkout_url: string, created_at: string, updated_at: string, store_id: string, amount: string, merchant_reference_id: string, redirect_url: string}
     * @throws \Exception
     */
    public function initiateCheckout(OrderInterface $order)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $storeId = $order->getStoreId();

        $url = 'https://api.uat.ablr.com/api/v2/public/merchant/checkout/';
        if (!$this->config->isSandbox($storeId)) {
            $url = 'https://api.ablr.com/api/v2/public/merchant/checkout/';
        }

        $params = [
            'store_id' => $this->config->getStoreID($storeId),
            'amount' => sprintf('%.2f', $order->getGrandTotal()),
            'merchant_reference_id' => $order->getIncrementId(),
            'redirect_url' => $this->urlBuilder->getUrl(
                'ablr/payment/complete',
                [
                    'order_key' => $this->helper->encryptData($order->getIncrementId())
                ]
            ),
        ];


        $this->logger->info('POST ' . $url, $params);
        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->addHeader('Authorization', 'Bearer ' . $this->config->getSecretApi($storeId));
        $this->curl->post($url, $this->json->serialize($params));

        $output = $this->curl->getBody();
        $this->logger->info('Response:' . $output);

        $data = $this->json->unserialize($output);
        if (!$data['success']) {
            $message = $data['message'];

            if (isset($data['errors'])) {
                foreach ($data['errors'] as $key => $value) {
                    $message .= $key . ': ' . implode(';', $value);
                }
            }

            throw new LocalizedException(__($message));
        }

        return $data['data'];
    }

    /**
     * Get Checkout Details.
     *
     * @param string $checkoutId
     * @param mixed $storeId
     *
     * @return array{code: string, state: string}
     * @throws \Exception
     */
    public function getCheckoutDetails($checkoutId, $storeId)
    {
        $url = sprintf('https://api.uat.ablr.com/api/v2/public/merchant/checkout/%s/order/', $checkoutId);
        if (!$this->config->isSandbox($storeId)) {
            $url = sprintf('https://api.ablr.com/api/v2/public/merchant/checkout/%s/order/', $checkoutId);
        }

        $this->logger->info('GET ' . $url);
        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->addHeader('Authorization', 'Bearer ' . $this->config->getSecretApi($storeId));
        $this->curl->get($url);

        $output = $this->curl->getBody();
        $this->logger->info('Response:' . $output);

        $data = $this->json->unserialize($output);
        if (!$data['success']) {
            throw new LocalizedException(__($data['message']));
        }

        return $data['data'];
    }
}
