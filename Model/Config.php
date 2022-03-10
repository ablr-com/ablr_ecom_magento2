<?php

namespace Ablr\Payment\Model;

use Magento\Store\Model\ScopeInterface;

class Config extends \Magento\Framework\App\Config
{
    const XML_PATH_PAYMENT_SANDBOX = 'payment/ablr/sandbox';
    const XML_PATH_PAYMENT_STOREID = 'payment/ablr/store_id';
    const XML_PATH_PAYMENT_SECRET_API_KEY = 'payment/ablr/secret_api_key';

    /**
     * Is Sandbox.
     *
     * @param $storeId
     *
     * @return bool
     */
    public function isSandbox($storeId)
    {
        return $this->isSetFlag(self::XML_PATH_PAYMENT_SANDBOX, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get Store ID.
     *
     * @param $storeId
     *
     * @return array|mixed
     */
    public function getStoreID($storeId)
    {
        return $this->getValue(self::XML_PATH_PAYMENT_STOREID, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get Secret API.
     *
     * @param $storeId
     *
     * @return array|mixed
     */
    public function getSecretApi($storeId)
    {
        return $this->getValue(self::XML_PATH_PAYMENT_SECRET_API_KEY, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
